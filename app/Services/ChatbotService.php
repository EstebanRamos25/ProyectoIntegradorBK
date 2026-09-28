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
        if ($provider === 'groq') {
            return $this->chatWithGroq($message, $module);
        }

        return $this->chatWithOpenAI($message, $module);
    }

    private function buildSystemPrompt(?string $module): string
    {
        $prompt = "Eres AVOBOT, el asistente virtual oficial de AVODAH.\n" .
            "Siempre respondes en ESPAÑOL, con un tono amable, profesional y muy claro.\n" .
            "Tu prioridad es ayudar a los usuarios del sistema interno de AVODAH a gestionar proyectos, productos, inventarios, escenas y ventas, y también responder dudas frecuentes sobre la empresa.\n\n" .
            "INFORMACIÓN OFICIAL DE AVODAH (usa esto como fuente principal):\n" .
            "- AVODAH es una empresa especializada en la fabricación y venta de piezas y acabados cerámicos.\n" .
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

    private function chatWithGroq(string $message, ?string $module): string
    {
        $apiKey = config('services.groq.api_key');
        $model  = config('services.groq.model', 'openai/gpt-oss-20b');

        if (!$apiKey) {
            return 'No hay API key configurada para Groq. Configura GROQ_API_KEY en .env';
        }

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_producto_en_bd',
                    'description' => 'Busca productos en la base de datos por nombre, categoría, color o descripción, y devuelve la cantidad de stock (cajas) disponible.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'busqueda' => [
                                'type' => 'string',
                                'description' => 'Término de búsqueda simple (ej. "porcelanato negro", "madera")',
                            ]
                        ],
                        'required' => ['busqueda']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'resumen_estadisticas_generales',
                    'description' => 'Devuelve un resumen general de totales (usuarios, productos, ventas) de la base de datos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass()
                    ]
                ]
            ]
        ];

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($module)],
            ['role' => 'user',   'content' => $message],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto',
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if (!$response->successful()) {
                $status = $response->status();
                $err = $response->json('error.message') ?? $response->body();
                return "Error Groq ({$status}): " . mb_substr($err, 0, 200);
            }

            $data = $response->json();
            $responseMessage = $data['choices'][0]['message'] ?? [];

            // Si el modelo decide llamar a una herramienta (Function Calling)
            if (isset($responseMessage['tool_calls']) && count($responseMessage['tool_calls']) > 0) {
                $messages[] = $responseMessage; // Añadir la llamada a la conversación
                
                foreach ($responseMessage['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $functionArgs = json_decode($toolCall['function']['arguments'], true) ?? [];
                    
                    $functionResult = '';
                    if ($functionName === 'buscar_producto_en_bd') {
                        $functionResult = $this->ejecutarBuscarProducto($functionArgs['busqueda'] ?? '');
                    } elseif ($functionName === 'resumen_estadisticas_generales') {
                        $functionResult = $this->ejecutarEstadisticasGenerales();
                    } else {
                        $functionResult = 'Herramienta no implementada.';
                    }

                    // Enviar el resultado de vuelta a Groq
                    $messages[] = [
                        'tool_call_id' => $toolCall['id'],
                        'role' => 'tool',
                        'name' => $functionName,
                        'content' => $functionResult,
                    ];
                }

                $secondPayload = [
                    'model' => $model,
                    'messages' => $messages,
                ];

                $secondResponse = Http::withToken($apiKey)
                    ->timeout(20)
                    ->post('https://api.groq.com/openai/v1/chat/completions', $secondPayload);
                
                if ($secondResponse->successful()) {
                    return $secondResponse->json('choices.0.message.content') ?? 'Respuesta vacía tras tool_call.';
                }
                return 'Falló la segunda llamada a Groq: ' . $secondResponse->status();
            }

            // Si no usó herramientas, devolver el texto directo
            return $responseMessage['content'] ?? 'Respuesta vacía de Groq.';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Groq Error', ['error' => $e->getMessage()]);
            return 'Error de comunicación con Groq: ' . $e->getMessage();
        }
    }

    private function ejecutarBuscarProducto(string $busqueda): string
    {
        if (strlen(trim($busqueda)) < 2) return 'Término de búsqueda muy corto.';
        
        $terms = explode(' ', trim($busqueda));
        $q = \App\Models\Producto::query();
        
        foreach($terms as $term) {
            if(strlen($term) < 2) continue;
            $term = rtrim($term, 's');
            $q->where(function($sub) use ($term) {
                $sub->where('Nombre', 'LIKE', "%{$term}%")
                    ->orWhere('Descripcion', 'LIKE', "%{$term}%")
                    ->orWhereHas('categoria', function($c) use ($term) {
                        $c->where('Nombre', 'LIKE', "%{$term}%");
                    });
            });
        }
        
        $prods = $q->limit(10)->get();
        if ($prods->isEmpty()) {
            return "No se encontraron productos en la BD que coincidan con '{$busqueda}'.";
        }
        
        $msg = "Resultados encontrados en la BD para '{$busqueda}':\n";
        foreach($prods as $p) {
            $stock = \App\Models\Inventario::where('producto_id', $p->id)->sum('Cajas_Disponibles');
            $msg .= "- Producto ID {$p->id}: {$p->Nombre}. Precio: Bs {$p->Precio}. Stock disponible: " . ((int)$stock) . " cajas.\n";
        }
        return $msg;
    }

    private function ejecutarEstadisticasGenerales(): string
    {
        $users = \App\Models\User::count();
        $prods = \App\Models\Producto::count();
        $ventas = \App\Models\Venta::count();
        return "La base de datos tiene actualmente: {$users} usuarios registrados, {$prods} productos en catálogo, y {$ventas} ventas históricas.";
    }

}
