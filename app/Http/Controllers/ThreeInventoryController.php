<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreeInventoryController extends Controller
{
    public function snapshot(Request $request): JsonResponse
    {
        $raw = $request->query('product_ids');

        $productIds = [];
        if (is_string($raw)) {
            $productIds = array_filter(array_map('intval', preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        } elseif (is_array($raw)) {
            $productIds = array_filter(array_map('intval', $raw));
        }

        $productIds = array_values(array_unique(array_filter($productIds, fn (int $v): bool => $v > 0)));

        if (count($productIds) === 0) {
            return response()->json([
                'message' => 'Debe enviar product_ids (ej: ?product_ids=1,2,3).',
            ], 422);
        }

        if (count($productIds) > 25) {
            return response()->json([
                'message' => 'Máximo 25 productos por consulta.',
            ], 422);
        }

        $existingIds = Producto::query()
            ->whereIn('id', $productIds)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $missing = array_values(array_diff($productIds, $existingIds));
        if (count($missing) > 0) {
            return response()->json([
                'message' => 'Algunos productos no existen.',
                'missing_ids' => $missing,
            ], 422);
        }

        $rows = Inventario::query()
            ->whereIn('producto_id', $productIds)
            ->selectRaw('producto_id, Codigo_Lote as lot_code, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as boxes')
            ->groupBy('producto_id', 'lot_code')
            ->get()
            ->map(function ($row): array {
                return [
                    'product_id' => (int) ($row->producto_id ?? 0),
                    'lot_code' => $row->lot_code !== null ? (string) $row->lot_code : null,
                    'boxes' => (int) ($row->boxes ?? 0),
                ];
            });

        $grouped = $rows->groupBy('product_id');

        $items = collect($productIds)
            ->map(function (int $productId) use ($grouped): array {
                $lots = $grouped
                    ->get($productId, collect())
                    ->filter(fn (array $r) => (int) ($r['boxes'] ?? 0) > 0)
                    ->map(fn (array $r) => [
                        'lot_code' => $r['lot_code'],
                        'boxes_available' => (int) $r['boxes'],
                    ])
                    ->values();

                return [
                    'product_id' => $productId,
                    'inventory' => [
                        'boxes_available_total' => (int) $lots->sum('boxes_available'),
                        'lots' => $lots,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
