<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Promocion;
use App\Models\Producto;
use App\Models\ThreeQuote;
use App\Models\ThreeScene;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ThreeQuotationController extends Controller
{
    public function generate(Request $request): Response
    {
        $validated = $request->validate([
            'material_id' => ['nullable', 'integer', 'exists:productos,id'],
            'scene_id' => ['nullable', 'integer', 'exists:three_scenes,id'],
            'scene_name' => ['nullable', 'string', 'max:120'],
            'floor_kind' => ['nullable', 'string', 'max:40'],
            'walls.material_id' => ['nullable', 'integer', 'exists:productos,id'],
            'walls.piece.kind' => ['nullable', 'string', 'in:tile,plank'],
            'walls.piece.width_cm' => ['nullable', 'numeric', 'min:10', 'max:400'],
            'walls.piece.depth_cm' => ['nullable', 'numeric', 'min:10', 'max:400'],
            'snapshot_top_png_data_url' => ['nullable', 'string', 'max:5000000'],
            'room.width_cm' => ['required', 'numeric', 'min:200', 'max:3000'],
            'room.depth_cm' => ['required', 'numeric', 'min:200', 'max:3000'],
            'room.height_cm' => ['required', 'numeric', 'min:200', 'max:800'],
            'piece.kind' => ['required', 'string', 'in:tile,plank'],
            'piece.width_cm' => ['required', 'numeric', 'min:10', 'max:400'],
            'piece.depth_cm' => ['required', 'numeric', 'min:10', 'max:400'],
            'piece.height_cm' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        $catalogItem = config('three_quotation.catalog.'.data_get($validated, 'piece.kind'));
        abort_unless(is_array($catalogItem), 422, 'Elemento no soportado para la cotización.');

        $room = [
            'width_cm' => (float) data_get($validated, 'room.width_cm'),
            'depth_cm' => (float) data_get($validated, 'room.depth_cm'),
            'height_cm' => (float) data_get($validated, 'room.height_cm'),
        ];

        $piece = [
            'kind' => (string) data_get($validated, 'piece.kind'),
            'label' => (string) ($catalogItem['label'] ?? 'Elemento'),
            'width_cm' => (float) data_get($validated, 'piece.width_cm'),
            'depth_cm' => (float) data_get($validated, 'piece.depth_cm'),
            'height_cm' => (float) data_get($validated, 'piece.height_cm', 12),
            'accent' => (string) ($catalogItem['accent'] ?? '#cbd5e1'),
        ];

        $floorAreaM2 = round(($room['width_cm'] / 100) * ($room['depth_cm'] / 100), 2);
        $pieceAreaM2 = round(($piece['width_cm'] / 100) * ($piece['depth_cm'] / 100), 4);
        $estimatedUnits = max(1, (int) ceil($floorAreaM2 / max($pieceAreaM2, 0.0001)));

        // Paredes: cálculo real de precio y stock
        $wallsSection = null;
        $wallsMaterialId = data_get($validated, 'walls.material_id');
        if ($wallsMaterialId) {
            $wallAreaM2 = round(2 * (($room['width_cm'] / 100) + ($room['depth_cm'] / 100)) * ($room['height_cm'] / 100), 2);

            $wallPieceWidthCm = (float) data_get($validated, 'walls.piece.width_cm', 0);
            $wallPieceDepthCm = (float) data_get($validated, 'walls.piece.depth_cm', 0);
            $wallPieceAreaM2 = null;
            $wallEstimatedUnits = null;

            if ($wallPieceWidthCm > 0 && $wallPieceDepthCm > 0) {
                $wallPieceAreaM2 = round(($wallPieceWidthCm / 100) * ($wallPieceDepthCm / 100), 4);
                $wallEstimatedUnits = max(1, (int) ceil($wallAreaM2 / max($wallPieceAreaM2, 0.0001)));
            }

            $wallProducto = Producto::query()->with(['categoria'])->find($wallsMaterialId);
            $wallUnitPriceM2 = $wallProducto ? (float) ($wallProducto->Precio ?? 0) : 0.0;
            $wallEstimatedTotal = round($wallAreaM2 * $wallUnitPriceM2, 0);

            $wallM2PerBox = $wallProducto ? (float) ($wallProducto->M2_Por_Caja ?? 0) : 0.0;
            $wallBoxesRequired = null;
            if ($wallM2PerBox > 0) {
                $wallBoxesRequired = max(1, (int) ceil($wallAreaM2 / $wallM2PerBox));
            }

            $wallInventoryCheck = null;
            if ($wallProducto && $wallBoxesRequired !== null) {
                $wallLotsAgg = Inventario::query()
                    ->where('producto_id', $wallProducto->id)
                    ->selectRaw('Codigo_Lote as lot_code, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as boxes')
                    ->groupBy('lot_code')
                    ->get()
                    ->map(function ($row): array {
                        return [
                            'lot_code' => $row->lot_code !== null ? (string) $row->lot_code : null,
                            'boxes_available' => (int) ($row->boxes ?? 0),
                        ];
                    })
                    ->filter(fn (array $r) => (int) $r['boxes_available'] > 0)
                    ->values();

                $wallBoxesAvailableTotal = (int) $wallLotsAgg->sum('boxes_available');
                $wallBestLot = $wallLotsAgg->sortByDesc('boxes_available')->first();
                $wallBestLotBoxes = $wallBestLot ? (int) $wallBestLot['boxes_available'] : 0;
                $wallBestLotCode = $wallBestLot['lot_code'] ?? null;

                $wallInventoryCheck = [
                    'product_id' => (int) $wallProducto->id,
                    'boxes_required' => $wallBoxesRequired,
                    'boxes_available_total' => $wallBoxesAvailableTotal,
                    'missing_boxes' => max(0, $wallBoxesRequired - $wallBoxesAvailableTotal),
                    'can_fulfill_total' => $wallBoxesAvailableTotal >= $wallBoxesRequired,
                    'can_fulfill_single_lot' => $wallBestLotBoxes >= $wallBoxesRequired,
                    'best_lot_code' => $wallBestLotCode,
                    'best_lot_boxes_available' => $wallBestLotBoxes,
                    'lots' => $wallLotsAgg,
                ];
            }

            $wallsSection = [
                'wall_area_m2' => $wallAreaM2,
                'piece_area_m2' => $wallPieceAreaM2,
                'estimated_units' => $wallEstimatedUnits,
                'unit_price_m2' => $wallUnitPriceM2,
                'estimated_total' => $wallEstimatedTotal,
                'boxes_required' => $wallBoxesRequired,
                'm2_per_box' => $wallM2PerBox > 0 ? $wallM2PerBox : null,
                'inventory_check' => $wallInventoryCheck,
                'piece' => ($wallPieceWidthCm > 0 && $wallPieceDepthCm > 0) ? [
                    'kind' => (string) (data_get($validated, 'walls.piece.kind') ?? ''),
                    'width_cm' => $wallPieceWidthCm,
                    'depth_cm' => $wallPieceDepthCm,
                ] : null,
                'material' => $wallProducto ? [
                    'id' => (int) $wallProducto->id,
                    'name' => (string) ($wallProducto->Nombre ?? ''),
                    'brand' => (string) ($wallProducto->Marca ?? ''),
                    'model' => (string) ($wallProducto->Modelo ?? ''),
                    'unit_sale' => (string) ($wallProducto->Unidad_Venta ?: 'caja'),
                    'pieces_per_box' => $wallProducto->Piezas_Por_Caja !== null ? (int) $wallProducto->Piezas_Por_Caja : null,
                    'category' => [
                        'id' => (int) ($wallProducto->categoria_id ?? 0),
                        'name' => (string) (optional($wallProducto->categoria)->Nombre ?? ''),
                    ],
                ] : null,
            ];
        }

        // Si el frontend manda un material real (Producto), usamos su precio y empaques.
        $materialId = $validated['material_id'] ?? null;
        $producto = null;
        if ($materialId) {
            $producto = Producto::query()->with(['categoria'])->find($materialId);
        }

        $unitPriceM2 = $producto ? (float) ($producto->Precio ?? 0) : (float) ($catalogItem['price_per_m2'] ?? 0);
        $estimatedUnitPrice = round($pieceAreaM2 * $unitPriceM2, 0);
        $estimatedTotal = round($floorAreaM2 * $unitPriceM2, 0);

        // Promoción automática por umbral de m² (si existe)
        $appliedPromotion = Promocion::query()
            ->where('Activo', true)
            ->whereNotNull('Min_M2')
            ->where('Min_M2', '<=', $floorAreaM2)
            ->orderByDesc('Min_M2')
            ->orderByDesc('Descuento')
            ->first();

        $discountPct = $appliedPromotion ? (float) ($appliedPromotion->Descuento ?? 0) : 0.0;
        $discountPct = max(0.0, min(100.0, $discountPct));
        $discountAmount = $discountPct > 0 ? round($estimatedTotal * ($discountPct / 100), 0) : 0.0;
        $totalAfterDiscount = max(0.0, $estimatedTotal - $discountAmount);

        // Inventario / lote (por cajas)
        $m2PerBox = $producto ? (float) ($producto->M2_Por_Caja ?? 0) : 0.0;
        $boxesRequired = null;
        if ($m2PerBox > 0) {
            $boxesRequired = max(1, (int) ceil($floorAreaM2 / $m2PerBox));
        }

        $inventoryCheck = null;
        if ($producto && $boxesRequired !== null) {
            $lotsAgg = Inventario::query()
                ->where('producto_id', $producto->id)
                ->selectRaw('Codigo_Lote as lot_code, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as boxes')
                ->groupBy('lot_code')
                ->get()
                ->map(function ($row): array {
                    return [
                        'lot_code' => $row->lot_code !== null ? (string) $row->lot_code : null,
                        'boxes_available' => (int) ($row->boxes ?? 0),
                    ];
                })
                ->filter(fn (array $r) => (int) $r['boxes_available'] > 0)
                ->values();

            $boxesAvailableTotal = (int) $lotsAgg->sum('boxes_available');
            $bestLot = $lotsAgg->sortByDesc('boxes_available')->first();
            $bestLotBoxes = $bestLot ? (int) $bestLot['boxes_available'] : 0;

            $bestLotCode = $bestLot['lot_code'] ?? null;

            $inventoryCheck = [
                'product_id' => (int) $producto->id,
                'boxes_required' => $boxesRequired,
                'boxes_available_total' => $boxesAvailableTotal,
                'missing_boxes' => max(0, $boxesRequired - $boxesAvailableTotal),
                'can_fulfill_total' => $boxesAvailableTotal >= $boxesRequired,
                'can_fulfill_single_lot' => $bestLotBoxes >= $boxesRequired,
                'best_lot_code' => $bestLotCode,
                'best_lot_boxes_available' => $bestLotBoxes,
                'lots' => $lotsAgg,
            ];
        }

        $quotation = [
            'scene_name' => (string) ($validated['scene_name'] ?? 'Escena 3D personalizada'),
            'generated_at' => now(),
            'currency' => (string) config('three_quotation.currency', 'BOB'),
            'currency_symbol' => (string) config('three_quotation.currency_symbol', 'Bs'),
            'prices_are_reference' => (bool) config('three_quotation.prices_are_reference', true),
            'floor_kind' => $this->floorLabel((string) ($validated['floor_kind'] ?? 'custom')),
            'walls' => $wallsSection,
            'snapshot_top_png_data_url' => $this->sanitizeSnapshotDataUrl($validated['snapshot_top_png_data_url'] ?? null),
            'material' => $producto ? [
                'id' => (int) $producto->id,
                'name' => (string) ($producto->Nombre ?? ''),
                'brand' => (string) ($producto->Marca ?? ''),
                'model' => (string) ($producto->Modelo ?? ''),
                'unit_sale' => (string) ($producto->Unidad_Venta ?: 'caja'),
                'm2_per_box' => $m2PerBox > 0 ? $m2PerBox : null,
                'pieces_per_box' => $producto->Piezas_Por_Caja !== null ? (int) $producto->Piezas_Por_Caja : null,
                'category' => [
                    'id' => (int) ($producto->categoria_id ?? 0),
                    'name' => (string) (optional($producto->categoria)->Nombre ?? ''),
                ],
            ] : null,
            'room' => $room,
            'piece' => $piece,
            'summary' => [
                'floor_area_m2' => $floorAreaM2,
                'piece_area_m2' => $pieceAreaM2,
                'estimated_units' => $estimatedUnits,
                'unit_price_m2' => $unitPriceM2,
                'estimated_unit_price' => $estimatedUnitPrice,
                'estimated_total' => $estimatedTotal,
                'subtotal' => $estimatedTotal,
                'discount_pct' => $discountPct > 0 ? $discountPct : null,
                'discount_amount' => $discountPct > 0 ? $discountAmount : null,
                'total_after_discount' => $discountPct > 0 ? $totalAfterDiscount : $estimatedTotal,
                'boxes_required' => $boxesRequired,
                'm2_per_box' => $m2PerBox > 0 ? $m2PerBox : null,
            ],
            'promotion' => $appliedPromotion && $discountPct > 0 ? [
                'id' => (int) $appliedPromotion->id,
                'name' => (string) ($appliedPromotion->Nombre ?? ''),
                'min_m2' => $appliedPromotion->Min_M2 !== null ? (float) $appliedPromotion->Min_M2 : null,
                'discount_pct' => $discountPct,
            ] : null,
            'inventory_check' => $inventoryCheck,
            'plan_svg' => $this->buildPlanSvg($room, $piece),
        ];

        // Persistir cotización por escena (para ver/enviar desde el menú).
        $sceneId = $validated['scene_id'] ?? null;
        $quote = null;
        if ($sceneId && $request->user()) {
            $scene = ThreeScene::query()
                ->whereKey((int) $sceneId)
                ->where('user_id', (int) $request->user()->id)
                ->firstOrFail();

            $quote = ThreeQuote::query()
                ->where('three_scene_id', $scene->id)
                ->where('user_id', (int) $request->user()->id)
                ->where('status', 'draft')
                ->orderByDesc('id')
                ->first();

            if (!$quote) {
                $quote = new ThreeQuote();
                $quote->three_scene_id = (int) $scene->id;
                $quote->user_id = (int) $request->user()->id;
                $quote->status = 'draft';
            }

            $quote->quotation = $quotation;
            $quote->producto_id = $producto?->id;
            $quote->boxes_required = $boxesRequired;
            $quote->area_m2 = $floorAreaM2;
            
            $wallTotal = isset($quotation['walls']['estimated_total']) ? (float) $quotation['walls']['estimated_total'] : 0.0;
            $quote->total = (float) $quotation['summary']['total_after_discount'] + $wallTotal;
            $quote->save();
        }

        $pdf = Pdf::loadView('three.quotation', [
            'quotation' => $quotation,
        ])->setPaper('a4', 'portrait');

        $pdfOutput = $pdf->output();

        if ($quote && $request->user()) {
            $pdfPath = 'three-quotes/u'.$request->user()->id.'/scene'.$quote->three_scene_id.'/cotizacion-'.$quote->id.'.pdf';
            Storage::disk('public')->put($pdfPath, $pdfOutput);
            $quote->pdf_path = $pdfPath;
            $quote->save();
        }

        return response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cotizacion-escena-3d-'.now()->format('Ymd_His').'.pdf"',
        ]);
    }

    protected function sanitizeSnapshotDataUrl(?string $dataUrl): ?string
    {
        if (!$dataUrl) {
            return null;
        }

        $dataUrl = trim($dataUrl);

        // Solo permitimos PNG embebido para DomPDF.
        if (!str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return null;
        }

        // Límite adicional por seguridad/memoria (base64). 5MB de string ya se valida arriba.
        if (strlen($dataUrl) > 5_000_000) {
            return null;
        }

        return $dataUrl;
    }

    protected function floorLabel(string $floorKind): string
    {
        return match ($floorKind) {
            'wood' => 'Piso madera',
            'ceramic' => 'Piso cerámica',
            default => 'Piso configurado en escena',
        };
    }

    protected function buildPlanSvg(array $room, array $piece): string
    {
        $canvasWidth = 720.0;
        $canvasHeight = 390.0;
        $padding = 42.0;
        $roomWidthM = max($room['width_cm'] / 100, 0.1);
        $roomDepthM = max($room['depth_cm'] / 100, 0.1);
        $scale = min(
            ($canvasWidth - ($padding * 2)) / $roomWidthM,
            ($canvasHeight - ($padding * 2)) / $roomDepthM
        );

        $roomWidth = round($roomWidthM * $scale, 2);
        $roomHeight = round($roomDepthM * $scale, 2);
        $roomX = round(($canvasWidth - $roomWidth) / 2, 2);
        $roomY = round(($canvasHeight - $roomHeight) / 2, 2);
        $roomRight = round($roomX + $roomWidth, 2);
        $roomBottom = round($roomY + $roomHeight, 2);
        $roomCenterX = round($roomX + ($roomWidth / 2), 2);
        $roomCenterY = round($roomY + ($roomHeight / 2), 2);
        $roomTopGuideY = round($roomY - 18, 2);
        $roomTopGuideY1 = round($roomY - 24, 2);
        $roomTopGuideY2 = round($roomY - 12, 2);
        $roomLeftGuideX = round($roomX - 18, 2);
        $roomLeftGuideX1 = round($roomX - 24, 2);
        $roomLeftGuideX2 = round($roomX - 12, 2);
        $roomLeftTextX = round($roomX - 28, 2);
        $textX = round($canvasWidth - 16, 2);

        $pieceWidth = max(round(($piece['width_cm'] / 100) * $scale, 2), 6);
        $pieceHeight = max(round(($piece['depth_cm'] / 100) * $scale, 2), 6);
        $accent = preg_match('/^#[0-9A-Fa-f]{6}$/', $piece['accent']) ? $piece['accent'] : '#cbd5e1';

        return <<<SVG
<svg width="100%" viewBox="0 0 {$canvasWidth} {$canvasHeight}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Plano superior de cobertura">
    <defs>
        <pattern id="piece-grid" x="0" y="0" width="{$pieceWidth}" height="{$pieceHeight}" patternUnits="userSpaceOnUse">
            <rect x="0" y="0" width="{$pieceWidth}" height="{$pieceHeight}" fill="{$accent}" fill-opacity="0.22" />
            <rect x="0.5" y="0.5" width="{$pieceWidth}" height="{$pieceHeight}" fill="none" stroke="#ffffff" stroke-opacity="0.55" stroke-width="1" />
        </pattern>
    </defs>

    <rect x="0" y="0" width="{$canvasWidth}" height="{$canvasHeight}" rx="22" fill="#f8fafc" />
    <rect x="{$roomX}" y="{$roomY}" width="{$roomWidth}" height="{$roomHeight}" rx="12" fill="#ffffff" stroke="#0f172a" stroke-width="2.5" />
    <rect x="{$roomX}" y="{$roomY}" width="{$roomWidth}" height="{$roomHeight}" rx="12" fill="url(#piece-grid)" />

    <text x="{$textX}" y="32" text-anchor="end" font-size="16" font-family="DejaVu Sans, sans-serif" fill="#0f172a">Plano superior estimado</text>
    <text x="{$textX}" y="54" text-anchor="end" font-size="11" font-family="DejaVu Sans, sans-serif" fill="#475569">Cobertura visual aproximada del elemento sobre el cuarto</text>

    <text x="{$textX}" y="84" text-anchor="end" font-size="12" font-family="DejaVu Sans, sans-serif" fill="#0f172a">Ancho cuarto: {$roomWidthM} m</text>
    <text x="{$textX}" y="104" text-anchor="end" font-size="12" font-family="DejaVu Sans, sans-serif" fill="#0f172a">Largo cuarto: {$roomDepthM} m</text>
    <text x="{$textX}" y="124" text-anchor="end" font-size="12" font-family="DejaVu Sans, sans-serif" fill="#0f172a">Pieza: {$piece['width_cm']} × {$piece['depth_cm']} cm</text>

    <line x1="{$roomX}" y1="{$roomTopGuideY}" x2="{$roomRight}" y2="{$roomTopGuideY}" stroke="#94a3b8" stroke-width="1.5" />
    <line x1="{$roomX}" y1="{$roomTopGuideY1}" x2="{$roomX}" y2="{$roomTopGuideY2}" stroke="#94a3b8" stroke-width="1.5" />
    <line x1="{$roomRight}" y1="{$roomTopGuideY1}" x2="{$roomRight}" y2="{$roomTopGuideY2}" stroke="#94a3b8" stroke-width="1.5" />
    <text x="{$roomCenterX}" y="{$roomTopGuideY1}" text-anchor="middle" font-size="12" font-family="DejaVu Sans, sans-serif" fill="#334155">{$roomWidthM} m</text>

    <line x1="{$roomLeftGuideX}" y1="{$roomY}" x2="{$roomLeftGuideX}" y2="{$roomBottom}" stroke="#94a3b8" stroke-width="1.5" />
    <line x1="{$roomLeftGuideX1}" y1="{$roomY}" x2="{$roomLeftGuideX2}" y2="{$roomY}" stroke="#94a3b8" stroke-width="1.5" />
    <line x1="{$roomLeftGuideX1}" y1="{$roomBottom}" x2="{$roomLeftGuideX2}" y2="{$roomBottom}" stroke="#94a3b8" stroke-width="1.5" />
    <text x="{$roomLeftTextX}" y="{$roomCenterY}" text-anchor="middle" font-size="12" font-family="DejaVu Sans, sans-serif" fill="#334155" transform="rotate(-90 {$roomLeftTextX} {$roomCenterY})">{$roomDepthM} m</text>
</svg>
SVG;
    }
}
