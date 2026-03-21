<?php

namespace App\Services;

use App\Services\Chatbot\DbInsightsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function __construct(
        private readonly DbInsightsService $dbInsights,
    ) {
    }

    public function chat(string $message, string $module = null): string
    {
        $provider = (string) config('services.chatbot.provider', 'ollama'); // por defecto ollama

        // 1) Consultas seguras a DB por módulo (si aplica)
        try {
            $dbAnswer = $this->dbInsights->answer($message, $module);
            if ($dbAnswer) {
                // Responder directo para mantenerlo rápido.
                return $dbAnswer;
            }
        } catch (\Throwable $e) {
            Log::warning('Chatbot DB insights failed', ['error' => $e->getMessage()]);
        }

        if ($provider === 'ollama') {
            return $this->chatWithOllama($message, $module);
        }

        return $this->chatWithOpenAI($message, $module);
    }

    private function buildSystemPrompt(?string $module): string
    {
        $prompt = "Eres CERABOT, el asistente virtual oficial de CERABOL.\n" .
            "Siempre respondes en ESPAÑOL, con un tono amable, profesional y muy claro.\n" .
            "Tu prioridad es ayudar a los usuarios del sistema interno de CERABOL a gestionar proyectos, productos, inventarios, escenas y ventas, y también responder dudas frecuentes sobre la empresa.\n\n" .
            "INFORMACIÓN OFICIAL DE CERABOL (usa esto como fuente principal):\n" .
            "- CERABOL es una empresa especializada en la fabricación y venta de piezas y acabados cerámicos.\n" .
            "- Horario: Lun-Vie 09:00-18:00, Sáb 10:00-14:00, Dom y festivos cerrado.\n" .
            "- Canales de contacto: teléfono, correo institucional y atención presencial.\n\n" .
            "PRODUCTOS Y SERVICIOS PRINCIPALES:\n" .
            "- Piezas cerámicas, acabados especiales y asesoría de proyectos.\n\n" .
            "COMPORTAMIENTO EN EL SISTEMA:\n" .
            "- Explica el módulo y sugiere acciones.\n" .
            "- Da pasos numerados cuando pidan instrucciones.\n" .
            "- Sé honesto si falta info y evita inventar datos sensibles.\n" .
            "- Respuestas breves y claras.\n" .
            "- Si solo saludan, preséntate y ofrece ayuda.\n";

        if ($module) {
            $prompt .= "\n\nContexto actual: MÓDULO = '{$module}'. Describe cómo ayudar en este módulo y luego responde.";
        }
        return $prompt;
    }

    private function buildOllamaSystemPrompt(?string $module): string
    {
        $prompt = "Eres CERABOT (CERABOL). Responde en español, breve y claro.\n" .
            "No inventes datos sensibles; si falta información, dilo.\n" .
            "Si te piden instrucciones, responde con pasos numerados.\n" .
            "Limita la respuesta a máximo 6 líneas o 5 viñetas.\n" .
            "CERABOL: piezas y acabados cerámicos. Horario: Lun-Vie 09:00-18:00; Sáb 10:00-14:00.\n";

        if ($module) {
            $prompt .= "Contexto: módulo actual = '{$module}'.\n";
        }

        return $prompt;
    }

    private function chatWithOpenAI(string $message, ?string $module): string
    {
        $apiKey = config('services.openai.api_key');
        $model  = config('services.openai.model', 'gpt-4.1-mini');

        if (!$apiKey) {
            return 'No hay API key configurada para OpenAI.';
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->buildSystemPrompt($module)],
                ['role' => 'user',   'content' => $message],
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                $status = $response->status();
                $json = $response->json();
                if ($status === 429) {
                    return 'El servicio de IA superó su cuota disponible (OpenAI 429).';
                }
                $detail = $json['error']['message'] ?? null;
                if ($detail) {
                    $detail = mb_substr($detail, 0, 200) . (mb_strlen($detail) > 200 ? '…' : '');
                }
                return 'Error OpenAI (' . $status . ')' . ($detail ? ': ' . $detail : '.');
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'Respuesta vacía de OpenAI.';
        } catch (\Throwable $e) {
            return 'Error de comunicación con OpenAI: ' . $e->getMessage();
        }
    }

    private function chatWithOllama(string $message, ?string $module): string
    {
        $url     = rtrim(config('services.ollama.url', 'http://127.0.0.1:11434'), '/');
        $model   = config('services.ollama.model', 'llama3');
        $timeout = (int) config('services.ollama.timeout', 60);
        // Importante: el frontend aborta alrededor de 120s. Para evitar que quede "Pensando..." y luego corte,
        // hacemos que el backend devuelva un error antes (dejando margen de red/parseo).
        $timeout = min($timeout, 110);

        // Objetivo: respuestas rápidas y simples
        $numPredict  = (int) env('OLLAMA_NUM_PREDICT', 60);
        $temperature = (float) env('OLLAMA_TEMPERATURE', 0.2);
        $topP        = (float) env('OLLAMA_TOP_P', 0.9);
        // Muy importante en máquinas con poca RAM: bajar num_ctx reduce el KV cache.
        // Con 6GB de RAM, 1024 suele ser mucho más rápido que 4096 (evita swap).
        $numCtx      = (int) env('OLLAMA_NUM_CTX', 1024);

        // Fallback (útil en Windows con poca RAM): probar modelos más ligeros si el principal falla.
        // Puedes sobreescribirlo con OLLAMA_FALLBACK_MODELS="qwen2.5:3b,phi3:mini"
        $fallbackModels = array_values(array_filter(array_map('trim', explode(',', (string) env('OLLAMA_FALLBACK_MODELS', 'llama3:latest')))));
        array_unshift($fallbackModels, $model);
        $fallbackModels = array_values(array_unique($fallbackModels));

        // Verificación de modelo (se puede saltar)
        if (!filter_var(env('OLLAMA_SKIP_MODEL_CHECK', false), FILTER_VALIDATE_BOOL)) {
            try {
                $tagsResp = Http::timeout(10)->get($url . '/api/tags');
                if ($tagsResp->successful()) {
                    $tags = $tagsResp->json();
                    $names = collect($tags['models'] ?? [])->pluck('name')->map(fn($n) => strtolower($n));
                    $needle = strtolower($model);
                    $found = $names->contains($needle) || $names->contains(fn($n) => explode(':', $n)[0] === $needle);
                    if (!$found) {
                        return "El modelo '{$model}' no está disponible. Ejecuta: ollama pull {$model}";
                    }
                }
            } catch (\Throwable $e) {
                // Ignorar fallo de verificación y continuar
            }
        }

        $payloadBase = [
            'messages' => [
                ['role' => 'system', 'content' => $this->buildOllamaSystemPrompt($module)],
                ['role' => 'user',   'content' => $message],
            ],
            'stream' => false,
            // Mantener el runner/modelo cargado para evitar cold-start entre preguntas.
            // Ejemplos: "30s", "5m", "10m".
            'keep_alive' => (string) env('OLLAMA_KEEP_ALIVE', '10m'),
            // Opciones compatibles con Ollama para acelerar respuesta
            'options' => [
                'num_predict' => $numPredict,
                'num_ctx' => $numCtx,
                'temperature' => $temperature,
                'top_p' => $topP,
            ],
        ];

        foreach ($fallbackModels as $candidateModel) {
            $payload = $payloadBase + ['model' => $candidateModel];

            // No reintentar timeouts: solo alarga la espera y el frontend terminará abortando.
            // Si el modelo está realmente lento, es mejor devolver un mensaje claro y que el usuario reintente.
            $localTimeout = $timeout;

            try {
                try {
                    $response = Http::timeout($localTimeout)
                        ->post($url . '/api/chat', $payload);

                    if (!$response->successful()) {
                        $status = $response->status();
                        $json = $response->json();
                        $detail = $json['error'] ?? ($json['message'] ?? null);

                        Log::warning('Ollama error', [
                            'status' => $status,
                            'model' => $candidateModel,
                            'detail' => $detail,
                        ]);

                        if ($status === 500 && is_string($detail)) {
                            if (stripos($detail, 'requires more system memory') !== false) {
                                // Intentar siguiente modelo (más liviano). Si ya no hay, devolver explicación clara.
                                break;
                            }
                            if (stripos($detail, 'not found') !== false) {
                                return "El modelo '{$candidateModel}' no está disponible en Ollama. Ejecuta: ollama pull {$candidateModel}";
                            }
                        }

                        return 'Error Ollama (' . $status . ')' . ($detail ? ': ' . mb_substr((string) $detail, 0, 200) : '.') ;
                    }

                    $data = $response->json();
                    if (isset($data['message']['content'])) {
                        return $data['message']['content'];
                    }
                    if (isset($data['messages']) && is_array($data['messages'])) {
                        $last = end($data['messages']);
                        if (isset($last['content'])) {
                            return is_array($last['content']) ? implode("\n", $last['content']) : $last['content'];
                        }
                    }
                    return 'Respuesta vacía de Ollama.';
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    Log::warning('Ollama communication error', [
                        'model' => $candidateModel,
                        'error' => $msg,
                    ]);

                    if (stripos($msg, 'cURL error 7') !== false || stripos($msg, 'Could not connect to server') !== false) {
                        return $this->formatOllamaConnectionHelp($url, (string) $model);
                    }
                    if (stripos($msg, 'cURL error 28') !== false) {
                        return 'El asistente está tardando demasiado en responder (timeout). Intenta de nuevo o usa una pregunta más específica/corta.';
                    }
                    return 'Error de comunicación con Ollama: ' . $msg;
                }
            } catch (\Throwable $e) {
                // Si algo raro pasa fuera del request HTTP, pasar al siguiente modelo.
            }
        }

        return 'Ollama no pudo generar respuesta con el modelo configurado. En Windows, esto suele pasar por RAM insuficiente; prueba un modelo más liviano (ej. qwen2.5:3b o phi3:mini) y configúralo en OLLAMA_MODEL.';
    }

    private function formatOllamaConnectionHelp(string $url, string $model): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = (string) (parse_url($url, PHP_URL_PORT) ?: '');

        $base = "No se pudo conectar a Ollama en {$url}.";

        // Mensaje más útil si apunta a localhost.
        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $portHint = $port !== '' ? ":{$port}" : '';
            return $base . "\n\n" .
                "Acciones sugeridas (Ubuntu/Debian):\n" .
                "1) Instala Ollama: curl -fsSL https://ollama.com/install.sh | sh\n" .
                "2) Inicia/activa el servicio: sudo systemctl enable --now ollama\n" .
                "3) Verifica que responda: curl -sS http://127.0.0.1{$portHint}/api/version\n" .
                "4) Descarga el modelo: ollama pull {$model}\n\n" .
                "Si estás ejecutando PHP/Laravel dentro de un contenedor, 127.0.0.1 apunta al contenedor: ajusta OLLAMA_URL en .env para que apunte al host (por ejemplo http://host.docker.internal{$portHint} o la IP del host) y ejecuta: php artisan config:clear.";
        }

        return $base . " Verifica que el host/puerto sean correctos y que Ollama esté en ejecución. (Config: OLLAMA_URL en .env; luego php artisan config:clear).";
    }
}
