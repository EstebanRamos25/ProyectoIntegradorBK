<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ThreeInteractionEvent;
use App\Models\Venta;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ThreeRecommendationService
{
    public function recommendForUserScene(int $userId, int $sceneId, int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));

        $categories = $this->predictCategoryScores($userId, $sceneId);
        $topCategories = collect($categories)
            ->sortDesc()
            ->take(3)
            ->keys()
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        if (empty($topCategories)) {
            $topCategories = $this->trendingCategories(3);
        }

        $recentProductIds = ThreeInteractionEvent::query()
            ->where('user_id', $userId)
            ->where('three_scene_id', $sceneId)
            ->whereIn('event_type', ['material_select', 'wall_material_select'])
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('producto_id')
            ->orderByDesc('created_at')
            ->limit(25)
            ->pluck('producto_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $products = Producto::query()
            ->with(['categoria'])
            ->whereIn('categoria_id', $topCategories)
            ->whereNotIn('id', $recentProductIds)
            ->limit(60)
            ->get();

        $productIds = $products->pluck('id')->map(fn ($v) => (int) $v)->all();
        $stockByProduct = $this->stockBoxesByProduct($productIds);
        $trendByProduct = $this->trendingProductsById();

        $items = $products
            ->map(function (Producto $p) use ($categories, $stockByProduct, $trendByProduct): array {
                $catId = (int) ($p->categoria_id ?? 0);
                $catScore = (float) ($categories[$catId] ?? 0);

                $stockBoxes = (int) ($stockByProduct[(int) $p->id] ?? 0);
                $trend = (float) ($trendByProduct[(int) $p->id] ?? 0);

                $score = $catScore + (0.15 * log(1 + $trend)) + (0.02 * log(1 + max(0, $stockBoxes)));

                return [
                    'product_id' => (int) $p->id,
                    'name' => (string) $p->Nombre,
                    'categoria_id' => $catId,
                    'categoria_name' => (string) (optional($p->categoria)->Nombre ?? ''),
                    'score' => round($score, 6),
                    'stock_boxes_available' => $stockBoxes,
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();

        // Cold start si no alcanzó (por haber excluido recientes)
        if (count($items) < $limit) {
            $fallback = $this->fallbackTrendingProducts($limit, array_column($items, 'product_id'));
            $items = array_values(array_merge($items, $fallback));
            $items = array_slice($items, 0, $limit);
        }

        return [
            'items' => $items,
            'meta' => [
                'scene_id' => $sceneId,
                'user_id' => $userId,
                'top_categories' => $topCategories,
                'model_loaded' => $this->hasModel(),
            ],
        ];
    }

    private function hasModel(): bool
    {
        return Storage::disk('local')->exists($this->modelPath());
    }

    private function modelPath(): string
    {
        return (string) config('three_reco.model_path', 'three-reco/model.json');
    }

    /**
     * @return array<int, float> categoria_id => score
     */
    private function predictCategoryScores(int $userId, int $sceneId): array
    {
        $trending = $this->trendingCategoryScores();

        if (!$this->hasModel()) {
            return $trending;
        }

        try {
            $raw = Storage::disk('local')->get($this->modelPath());
            $model = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $trending;
        }

        $vectorSize = (int) Arr::get($model, 'vector_size', 0);
        $hiddenSize = (int) Arr::get($model, 'hidden_size', 0);
        $classes = Arr::get($model, 'classes', []);
        $w1 = Arr::get($model, 'W1', []);
        $b1 = Arr::get($model, 'b1', []);
        $w2 = Arr::get($model, 'W2', []);
        $b2 = Arr::get($model, 'b2', []);

        if ($vectorSize <= 0 || $hiddenSize <= 0 || empty($classes) || empty($w1) || empty($w2)) {
            return $trending;
        }

        $x = $this->buildInputVector($userId, $sceneId, $vectorSize);
        if (count($x) !== $vectorSize) {
            return $trending;
        }

        // Hidden
        $h = array_fill(0, $hiddenSize, 0.0);
        for ($j = 0; $j < $hiddenSize; $j++) {
            $sum = (float) ($b1[$j] ?? 0);
            $row = $w1[$j] ?? [];
            for ($i = 0; $i < $vectorSize; $i++) {
                $sum += (float) ($row[$i] ?? 0) * (float) $x[$i];
            }
            $h[$j] = $sum > 0 ? $sum : 0.0; // ReLU
        }

        $out = count($classes);
        $logits = array_fill(0, $out, 0.0);
        for ($k = 0; $k < $out; $k++) {
            $sum = (float) ($b2[$k] ?? 0);
            $row = $w2[$k] ?? [];
            for ($j = 0; $j < $hiddenSize; $j++) {
                $sum += (float) ($row[$j] ?? 0) * (float) $h[$j];
            }
            $logits[$k] = $sum;
        }

        $probs = $this->softmax($logits);
        $scores = [];
        foreach ($classes as $idx => $catId) {
            $catId = (int) $catId;
            if ($catId <= 0) {
                continue;
            }
            $scores[$catId] = (float) ($probs[$idx] ?? 0);
        }

        // Mezclamos un poco de tendencia para estabilidad
        foreach ($trending as $catId => $trendScore) {
            $scores[$catId] = (float) (($scores[$catId] ?? 0) + (0.20 * $trendScore));
        }

        return $scores;
    }

    /**
     * @return float[]
     */
    public function buildInputVectorAt(int $userId, int $sceneId, int $vectorSize, Carbon $at): array
    {
        $v = array_fill(0, $vectorSize, 0.0);
        $add = function (string $key, float $value) use (&$v, $vectorSize): void {
            $idx = $this->fnv1aIndex($key, $vectorSize);
            $v[$idx] += $value;
        };

        $add('bias', 1.0);
        $add('user:'.$userId, 1.0);
        $add('scene:'.$sceneId, 1.0);

        $since = (clone $at)->subDays(30);
        $events = ThreeInteractionEvent::query()
            ->where('user_id', $userId)
            ->where('three_scene_id', $sceneId)
            ->whereBetween('created_at', [$since, $at])
            ->whereIn('event_type', ['material_select', 'wall_material_select', 'quote_generate'])
            ->get(['event_type', 'categoria_id', 'created_at']);

        $lastCat = null;
        foreach ($events->sortByDesc('created_at') as $ev) {
            $cid = (int) ($ev->categoria_id ?? 0);
            if ($cid > 0) {
                $lastCat = $cid;
                break;
            }
        }
        if ($lastCat) {
            $add('lastcat:'.$lastCat, 1.5);
        }

        $counts = [];
        $quoteCount = 0;
        foreach ($events as $ev) {
            if ($ev->event_type === 'quote_generate') {
                $quoteCount++;
                continue;
            }
            $cid = (int) ($ev->categoria_id ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $counts[$cid] = ($counts[$cid] ?? 0) + 1;
        }
        foreach ($counts as $cid => $c) {
            $add('selcat:'.$cid, (float) log(1 + $c));
        }
        if ($quoteCount > 0) {
            $add('quote_cnt', (float) log(1 + $quoteCount));
        }

        $trend = $this->trendingCategoryScores();
        foreach ($trend as $cid => $score) {
            $add('trendcat:'.$cid, (float) $score);
        }

        return $v;
    }

    /**
     * @return float[]
     */
    private function buildInputVector(int $userId, int $sceneId, int $vectorSize): array
    {
        return $this->buildInputVectorAt($userId, $sceneId, $vectorSize, now());
    }

    private function fnv1aIndex(string $key, int $mod): int
    {
        $hash = 2166136261;
        $len = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $hash ^= ord($key[$i]);
            $hash = ($hash * 16777619) & 0xffffffff;
        }

        return (int) ($hash % $mod);
    }

    /**
     * @param float[] $logits
     * @return float[]
     */
    private function softmax(array $logits): array
    {
        if (empty($logits)) {
            return [];
        }

        $max = max($logits);
        $exps = [];
        $sum = 0.0;
        foreach ($logits as $v) {
            $e = exp((float) $v - (float) $max);
            $exps[] = $e;
            $sum += $e;
        }
        if ($sum <= 0) {
            return array_fill(0, count($logits), 1.0 / max(1, count($logits)));
        }
        return array_map(fn ($e) => (float) ($e / $sum), $exps);
    }

    /**
     * @return array<int, float>
     */
    private function trendingCategoryScores(): array
    {
        return Cache::remember('three_reco.trending_categories', 600, function (): array {
            $since = now()->subDays(30)->toDateString();

            $rows = Venta::query()
                ->where('Fecha', '>=', $since)
                ->whereNotNull('producto_id')
                ->join('productos', 'ventas.producto_id', '=', 'productos.id')
                ->selectRaw('productos.categoria_id as categoria_id, COUNT(*) as c')
                ->groupBy('productos.categoria_id')
                ->get();

            $out = [];
            foreach ($rows as $row) {
                $cid = (int) ($row->categoria_id ?? 0);
                if ($cid <= 0) {
                    continue;
                }
                $out[$cid] = (float) log(1 + (int) ($row->c ?? 0));
            }
            arsort($out);
            return $out;
        });
    }

    /**
     * @return int[]
     */
    private function trendingCategories(int $limit): array
    {
        $scores = $this->trendingCategoryScores();
        return array_slice(array_map('intval', array_keys($scores)), 0, max(1, $limit));
    }

    /**
     * @param int[] $productIds
     * @return array<int, int>
     */
    private function stockBoxesByProduct(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $rows = Inventario::query()
            ->whereIn('producto_id', $productIds)
            ->selectRaw('producto_id, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as boxes')
            ->groupBy('producto_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $pid = (int) ($row->producto_id ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $out[$pid] = (int) ($row->boxes ?? 0);
        }
        return $out;
    }

    /**
     * @return array<int, float> producto_id => count
     */
    private function trendingProductsById(): array
    {
        return Cache::remember('three_reco.trending_products', 600, function (): array {
            $since = now()->subDays(60)->toDateString();
            $rows = Venta::query()
                ->where('Fecha', '>=', $since)
                ->whereNotNull('producto_id')
                ->selectRaw('producto_id, COUNT(*) as c')
                ->groupBy('producto_id')
                ->get();

            $out = [];
            foreach ($rows as $row) {
                $pid = (int) ($row->producto_id ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $out[$pid] = (float) ($row->c ?? 0);
            }
            arsort($out);
            return $out;
        });
    }

    /**
     * @param int[] $exclude
     * @return array<int, array>
     */
    private function fallbackTrendingProducts(int $limit, array $exclude = []): array
    {
        $exclude = array_map('intval', $exclude);
        $trend = $this->trendingProductsById();
        $topIds = array_values(array_diff(array_map('intval', array_keys($trend)), $exclude));
        $topIds = array_slice($topIds, 0, 80);

        if (empty($topIds)) {
            return [];
        }

        $products = Producto::query()
            ->with(['categoria'])
            ->whereIn('id', $topIds)
            ->get();

        $stock = $this->stockBoxesByProduct($products->pluck('id')->map(fn ($v) => (int) $v)->all());

        $items = $products->map(function (Producto $p) use ($trend, $stock): array {
            $pid = (int) $p->id;
            return [
                'product_id' => $pid,
                'name' => (string) $p->Nombre,
                'categoria_id' => (int) ($p->categoria_id ?? 0),
                'categoria_name' => (string) (optional($p->categoria)->Nombre ?? ''),
                'score' => (float) (log(1 + (float) ($trend[$pid] ?? 0))),
                'stock_boxes_available' => (int) ($stock[$pid] ?? 0),
            ];
        })->sortByDesc('score')->take($limit)->values()->all();

        return $items;
    }
}
