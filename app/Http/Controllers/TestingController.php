<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\ExecutePythonTest;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class TestingController extends Controller
{
    private function executeTest($script, $timeout, $reportName, $type)
    {
        $uuid = Str::uuid();
        $reportPath = base_path("testing-scripts/reports/{$reportName}_{$uuid}.html");
        
        // Almacenar parámetros en caché para el job
        Cache::put("test:{$uuid}", [
            'python_bin' => base_path('venv/Scripts/python.exe'),
            'script' => base_path("testing-scripts/{$script}"),
            'report_path' => $reportPath,
            'type' => $type
        ], now()->addHours(2));

        // Despachar trabajo en cola
        ExecutePythonTest::dispatch($uuid)->delay(now()->addSeconds(5));

        return redirect()->route('testing.status', $uuid);
    }

    public function smoke()
    {
        return $this->executeTest('smoke_tests.py', 300, 'smoke_report', 'html');
    }

    public function locust()
    {
        return $this->executeTest('prueba_locust.py', 600, 'locust_report', 'html');
    }

    public function status($uuid)
    {
        $status = Cache::get("test:{$uuid}:status", 'pending');
        $reportPath = Cache::get("test:{$uuid}:report");
        
        return view('testing.status', [
            'uuid' => $uuid,
            'status' => $status,
            'reportPath' => $reportPath
        ]);
    }

    public function downloadReport($uuid)
    {
        $reportPath = Cache::get("test:{$uuid}:report");
        
        if (file_exists($reportPath)) {
            return response()->file($reportPath, [
                'Content-Type' => mime_content_type($reportPath),
                'Content-Disposition' => 'inline'
            ]);
        }
        
        return back()->with('error', 'Reporte no encontrado');
    }
}