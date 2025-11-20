<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatbotService
{
    public function chat(string $message, string $module = null): string
    {
        $provider = env('CHAT_PROVIDER', 'ollama'); // por defecto ollama ahora

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
        $timeout = (int) config('services.ollama.timeout', 120); // subir default

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

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->buildSystemPrompt($module)],
                ['role' => 'user',   'content' => $message],
            ],
            'stream' => false,
        ];

        $attempts = 0;
        $maxAttempts = 2; // 1 retry
        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $response = Http::timeout($timeout)
                    ->post($url . '/api/chat', $payload);

                if (!$response->successful()) {
                    return 'Error Ollama (' . $response->status() . ').';
                }

                $data = $response->json();
                if (isset($data['message']['content'])) {
                    return $data['message']['content'];
                }
                if (isset($data['messages']) && is_array($data['messages'])) {
                    $last = end($data['messages']);
                    if (isset($last['content'])) return is_array($last['content']) ? implode("\n", $last['content']) : $last['content'];
                }
                return 'Respuesta vacía de Ollama.';
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'cURL error 28') !== false && $attempts < $maxAttempts) {
                    // aumentar ligeramente el timeout para el segundo intento
                    $timeout += 60;
                    continue; // retry
                }
                if (stripos($msg, 'cURL error 28') !== false) {
                    return 'Tiempo de espera agotado contactando a Ollama. Verifica que el servicio esté activo y el modelo cargado.';
                }
                return 'Error de comunicación con Ollama: ' . $msg;
            }
        }

        return 'No se pudo obtener respuesta de Ollama.';
    }
}
