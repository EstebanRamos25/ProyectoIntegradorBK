<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ThreeMaterialsController extends Controller
{
    public function index(): JsonResponse
    {
        $productos = Producto::query()
            ->with(['categoria', 'attachment'])
            ->orderBy('id', 'desc')
            ->get();

        $productIds = $productos->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Agregación de stock: usamos Cajas_Disponibles si existe; si no, caemos a Cantidad (legacy)
        /** @var Collection<int, array{product_id:int, lot_code:?string, boxes:int}> $invAgg */
        $invAgg = Inventario::query()
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

        $invByProduct = $invAgg->groupBy('product_id');

        $items = $productos
            ->map(function (Producto $producto) use ($invByProduct): ?array {
                $attachment = $producto->attachment('image')->first();
                $url = null;

                if ($attachment && is_object($attachment) && method_exists($attachment, 'url')) {
                    $url = $attachment->url();
                }

                if (empty($url)) {
                    return null;
                }

                $tipo = Str::lower((string) optional($producto->categoria)->Tipo_Material);
                $catNombre = Str::lower((string) optional($producto->categoria)->Nombre);
                $nombre = Str::lower((string) $producto->Nombre);

                $hayMadera = Str::contains($tipo, 'mader') || Str::contains($catNombre, 'mader') || Str::contains($nombre, 'mader');
                $hayCeramica = Str::contains($tipo, 'ceram') || Str::contains($tipo, 'porcel')
                    || Str::contains($catNombre, 'ceram') || Str::contains($catNombre, 'porcel')
                    || Str::contains($nombre, 'ceram') || Str::contains($nombre, 'porcel');

                $kind = $hayMadera ? 'plank' : ($hayCeramica ? 'tile' : 'tile');

                $rows = $invByProduct->get((int) $producto->id, collect());
                $lots = $rows
                    ->filter(fn (array $r) => (int) ($r['boxes'] ?? 0) > 0)
                    ->map(fn (array $r) => [
                        'lot_code' => $r['lot_code'],
                        'boxes_available' => (int) $r['boxes'],
                    ])
                    ->values();

                $totalBoxes = (int) $lots->sum('boxes_available');
                $m2PerBox = (float) ($producto->M2_Por_Caja ?? 0);
                $availableM2 = $m2PerBox > 0 ? round($totalBoxes * $m2PerBox, 4) : null;

                $pieceWidthCm = $producto->Ancho_Pieza_Cm !== null ? (float) $producto->Ancho_Pieza_Cm : null;
                $pieceDepthCm = $producto->Largo_Pieza_Cm !== null ? (float) $producto->Largo_Pieza_Cm : null;

                // Inferencia simple solo para cerámica: si no hay formato pero hay m²/caja y piezas/caja,
                // asumimos baldosa cuadrada con área promedio.
                if (($pieceWidthCm === null || $pieceDepthCm === null) && $kind === 'tile') {
                    $piecesPerBox = $producto->Piezas_Por_Caja !== null ? (int) $producto->Piezas_Por_Caja : 0;
                    if ($m2PerBox > 0 && $piecesPerBox > 0) {
                        $pieceAreaM2 = $m2PerBox / $piecesPerBox;
                        if ($pieceAreaM2 > 0) {
                            $sideCm = round(sqrt($pieceAreaM2) * 100, 2);
                            $pieceWidthCm = $pieceWidthCm ?? $sideCm;
                            $pieceDepthCm = $pieceDepthCm ?? $sideCm;
                        }
                    }
                }

                return [
                    'id' => (int) $producto->id,
                    'name' => (string) $producto->Nombre,
                    'kind' => $kind, // tile | plank (para compatibilidad con cotización)
                    'price_per_m2' => (float) ($producto->Precio ?? 0),
                    'image_url' => (string) $url,
                    'packaging' => [
                        'unit_sale' => (string) ($producto->Unidad_Venta ?: 'caja'),
                        'm2_per_box' => $m2PerBox > 0 ? $m2PerBox : null,
                        'pieces_per_box' => $producto->Piezas_Por_Caja !== null ? (int) $producto->Piezas_Por_Caja : null,
                    ],
                    'piece_dimensions_cm' => (
                        $pieceWidthCm !== null
                        && $pieceDepthCm !== null
                        && $pieceWidthCm > 0
                        && $pieceDepthCm > 0
                    ) ? [
                        'width' => $pieceWidthCm,
                        'depth' => $pieceDepthCm,
                        'locked' => true,
                    ] : null,
                    'inventory' => [
                        'boxes_available_total' => $totalBoxes,
                        'm2_available_total' => $availableM2,
                        'lots' => $lots,
                    ],
                    'category' => [
                        'id' => (int) ($producto->categoria_id ?? 0),
                        'name' => (string) (optional($producto->categoria)->Nombre ?? ''),
                        'tipo_material' => (string) (optional($producto->categoria)->Tipo_Material ?? ''),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'items' => $items,
            'count' => $items->count(),
        ]);
    }
}
