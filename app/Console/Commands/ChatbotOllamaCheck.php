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
            $msg = $e->getMessage();
            $this->error('Error: ' . $msg);

            if (stripos($msg, 'cURL error 7') !== false || stripos($msg, 'Could not connect to server') !== false) {
                $host = (string) parse_url($url, PHP_URL_HOST);
                $port = (string) (parse_url($url, PHP_URL_PORT) ?: '11434');
                $portHint = $port ? (':' . $port) : '';

                $this->newLine();
                if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
                    $this->line('Parece que Ollama no está instalado o no está corriendo localmente.');
                    $this->line('Ubuntu/Debian:');
                    $this->line('  1) curl -fsSL https://ollama.com/install.sh | sh');
                    $this->line('  2) sudo systemctl enable --now ollama');
                    $this->line("  3) curl -sS http://127.0.0.1{$portHint}/api/version");
                    $this->line("  4) ollama pull {$model}");
                    $this->newLine();
                    $this->line('Si estás en Docker/contendor: 127.0.0.1 apunta al contenedor.');
                    $this->line("Ajusta OLLAMA_URL (ej. http://host.docker.internal{$portHint} o IP del host) y ejecuta: php artisan config:clear");
                } else {
                    $this->line('Verifica conectividad al host/puerto y que Ollama esté en ejecución.');
                    $this->line('Recuerda: si cambias OLLAMA_URL, ejecuta: php artisan config:clear');
                }
            }
            return self::FAILURE;
        }
    }
}
