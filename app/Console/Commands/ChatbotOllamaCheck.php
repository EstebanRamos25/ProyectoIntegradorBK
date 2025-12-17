<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ChatbotOllamaCheck extends Command
{
    protected $signature = 'chatbot:ollama-check {--model=} {--url=}';

    protected $description = 'Verifica conectividad con Ollama y prueba un chat simple.';

    public function handle(): int
    {
        $url = rtrim($this->option('url') ?: config('services.ollama.url', 'http://127.0.0.1:11434'), '/');
        $model = $this->option('model') ?: config('services.ollama.model', 'llama3');

        $this->line("URL: {$url}");
        $this->line("Model: {$model}");

        try {
            $version = Http::timeout(5)->get($url . '/api/version');
            if (!$version->successful()) {
                $this->error('Ollama no responde a /api/version. HTTP ' . $version->status());
                return self::FAILURE;
            }
            $this->info('Ollama OK: ' . ($version->json('version') ?? 'version desconocida'));

            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Responde solo con "OK".'],
                ],
                'stream' => false,
                'options' => [
                    'num_predict' => 20,
                    'temperature' => 0.0,
                ],
            ];

            $resp = Http::timeout((int) config('services.ollama.timeout', 60))
                ->post($url . '/api/chat', $payload);

            if (!$resp->successful()) {
                $detail = $resp->json('error') ?? $resp->json('message');
                $this->error('Chat falló. HTTP ' . $resp->status() . ($detail ? ' - ' . $detail : ''));
                return self::FAILURE;
            }

            $content = $resp->json('message.content');
            $this->info('Chat OK: ' . ($content ?: '(vacío)'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
