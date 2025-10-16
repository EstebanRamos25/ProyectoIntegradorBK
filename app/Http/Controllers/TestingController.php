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
        $pythonBin = env('PYTHON_BIN');
        if (empty($pythonBin)) {
            // Fallbacks por sistema operativo
            $pythonBin = PHP_OS_FAMILY === 'Windows' ? base_path('venv/Scripts/python.exe') : '/usr/bin/python3';
        }

        Cache::put("test:{$uuid}", [
            'python_bin' => $pythonBin,
            'script' => base_path("testing-scripts/{$script}"),
            'report_path' => $reportPath,
            'type' => $type,
            'timeout' => $timeout,
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
        // Ajuste: usar el nombre de archivo correcto en testing-scripts
        return $this->executeTest('prueba_locust_ui.py', 600, 'locust_report', 'html');
    }

    public function status($uuid)
    {
        $status = Cache::get("test:{$uuid}:status", 'pending');
        $reportPath = Cache::get("test:{$uuid}:report");

        // Si la solicitud espera JSON, responder en JSON (para fetch)
        if (request()->expectsJson()) {
            return response()->json([
                'uuid' => $uuid,
                'status' => $status,
                'reportPath' => $reportPath,
            ]);
        }

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

    // Métodos faltantes referenciados por rutas
    public function testCases()
    {
        return view('testing.status', [
            'uuid' => 'manual',
            'status' => 'pending',
            'reportPath' => null,
        ]);
    }

    public function integration()
    {
        return view('testing.status', [
            'uuid' => 'manual',
            'status' => 'pending',
            'reportPath' => null,
        ]);
    }
}