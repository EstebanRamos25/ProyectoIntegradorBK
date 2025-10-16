<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class ExecutePythonTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function handle()
    {
        $data = Cache::get("test:{$this->uuid}");
        $process = new Process([
            $data['python_bin'], 
            $data['script'],
            '--report-path=' . $data['report_path']
        ]);
        
        $process->setTimeout(300);
        $process->run();
        
        if ($process->isSuccessful()) {
            Cache::put("test:{$this->uuid}:status", 'completed', now()->addHours(2));
            Cache::put("test:{$this->uuid}:report", $data['report_path'], now()->addHours(2));
        } else {
            Cache::put("test:{$this->uuid}:status", 'failed', now()->addHours(2));
            Log::error("Test failed: " . $process->getErrorOutput());
        }
    }
}