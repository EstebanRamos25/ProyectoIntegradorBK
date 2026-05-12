<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\ThreeQuote;
use App\Models\Venta;
use App\Services\ThreeRecommendationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class TrainThreeRecommender extends Command
{
    protected $signature = 'three:reco-train
        {--limit=2000 : Máximo de muestras}
        {--include-sent : Incluye cotizaciones enviadas (no vendidas) como señal adicional}' ;

    protected $description = 'Entrena un recomendador 3D (NN clasificación por categoría) y guarda el modelo en storage.';

    public function handle(ThreeRecommendationService $service): int
    {
        $vectorSize = (int) config('three_reco.vector_size', 128);
        $hiddenSize = (int) config('three_reco.hidden_size', 64);

        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? $limit : 2000;
        $includeSent = (bool) $this->option('include-sent');

        $pythonBin = env('PYTHON_BIN');
        if (empty($pythonBin)) {
            $pythonBin = PHP_OS_FAMILY === 'Windows' ? base_path('venv/Scripts/python.exe') : '/usr/bin/python3';
        }

        $datasetRel = (string) config('three_reco.dataset_path', 'three-reco/dataset.json');
        $modelRel = (string) config('three_reco.model_path', 'three-reco/model.json');

        Storage::disk('local')->makeDirectory(dirname($datasetRel));
        Storage::disk('local')->makeDirectory(dirname($modelRel));

        $this->info('Reco: generando dataset...');

        $samples = [];

        // Ventas reales
        $ventas = Venta::query()
            ->where('Origen', '3d_sale')
            ->whereNotNull('producto_id')
            ->whereNotNull('usuario_id')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'producto_id', 'usuario_id', 'three_quote_id', 'created_at', 'Fecha']);

        $quoteIds = $ventas->pluck('three_quote_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
        $quotesById = $quoteIds
            ? ThreeQuote::query()->whereIn('id', $quoteIds)->get(['id', 'three_scene_id'])->keyBy('id')
            : collect();

        foreach ($ventas as $v) {
            $productoId = (int) ($v->producto_id ?? 0);
            $userId = (int) ($v->usuario_id ?? 0);
            if ($productoId <= 0 || $userId <= 0) {
                continue;
            }

            $producto = Producto::query()->find($productoId);
            $catId = $producto && $producto->categoria_id ? (int) $producto->categoria_id : 0;
            if ($catId <= 0) {
                continue;
            }

            $sceneId = 0;
            if ($v->three_quote_id) {
                $q = $quotesById->get((int) $v->three_quote_id);
                $sceneId = $q ? (int) ($q->three_scene_id ?? 0) : 0;
            }

            $at = $v->created_at ?? null;
            if (!$at && $v->Fecha) {
                $at = $v->Fecha;
            }
            $at = $at ? \Illuminate\Support\Carbon::parse($at) : now();

            $x = $service->buildInputVectorAt($userId, $sceneId, $vectorSize, $at);
            $sparse = $this->denseToSparse($x);
            $samples[] = [
                'x_idx' => $sparse['idx'],
                'x_val' => $sparse['val'],
                'y_cat' => $catId,
            ];
        }

        // Cotizaciones 3D (señal de intención)
        $qLimit = max(0, $limit - count($samples));
        if ($qLimit > 0) {
            $q = ThreeQuote::query()
                ->whereIn('status', $includeSent ? ['sent', 'sold'] : ['sold'])
                ->whereNotNull('producto_id')
                ->whereNotNull('user_id')
                ->orderByDesc('created_at')
                ->limit($qLimit)
                ->get(['id', 'producto_id', 'user_id', 'three_scene_id', 'created_at']);

            foreach ($q as $quote) {
                $productoId = (int) ($quote->producto_id ?? 0);
                $userId = (int) ($quote->user_id ?? 0);
                $sceneId = (int) ($quote->three_scene_id ?? 0);
                if ($productoId <= 0 || $userId <= 0) {
                    continue;
                }

                $producto = Producto::query()->find($productoId);
                $catId = $producto && $producto->categoria_id ? (int) $producto->categoria_id : 0;
                if ($catId <= 0) {
                    continue;
                }

                $at = $quote->created_at ? \Illuminate\Support\Carbon::parse($quote->created_at) : now();
                $x = $service->buildInputVectorAt($userId, $sceneId, $vectorSize, $at);
                $sparse = $this->denseToSparse($x);
                $samples[] = [
                    'x_idx' => $sparse['idx'],
                    'x_val' => $sparse['val'],
                    'y_cat' => $catId,
                ];
            }
        }

        $payload = [
            'version' => 1,
            'vector_size' => $vectorSize,
            'hidden_size' => $hiddenSize,
            'samples' => $samples,
            'generated_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put($datasetRel, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->info('Reco: dataset guardado en '.$datasetRel);
        $this->info('Reco: muestras: '.count($samples));

        $script = base_path('ml/three_reco/train.py');
        if (!is_file($script)) {
            $this->error('No existe el script: '.$script);
            return self::FAILURE;
        }

        $this->info('Reco: entrenando (python)...');
        $datasetAbs = Storage::disk('local')->path($datasetRel);
        $modelAbs = Storage::disk('local')->path($modelRel);
        $process = new Process([
            $pythonBin,
            $script,
            '--dataset='.$datasetAbs,
            '--out='.$modelAbs,
        ]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Entrenamiento falló:');
            $this->line($process->getErrorOutput() ?: $process->getOutput());
            return self::FAILURE;
        }

        $this->info($process->getOutput());
        $this->info('Reco: modelo guardado en '.$modelRel);

        return self::SUCCESS;
    }

    /**
     * @param float[] $x
     * @return array{idx:int[], val:float[]}
     */
    private function denseToSparse(array $x): array
    {
        $idx = [];
        $val = [];
        foreach ($x as $i => $v) {
            $v = (float) $v;
            if ($v == 0.0) {
                continue;
            }
            $idx[] = (int) $i;
            $val[] = $v;
        }
        return ['idx' => $idx, 'val' => $val];
    }
}
