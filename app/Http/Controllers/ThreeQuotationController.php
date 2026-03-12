<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreeQuotationController extends Controller
{
    public function generate(Request $request): Response
    {
        $validated = $request->validate([
            'scene_name' => ['nullable', 'string', 'max:120'],
            'floor_kind' => ['nullable', 'string', 'max:40'],
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
        $unitPriceM2 = (float) ($catalogItem['price_per_m2'] ?? 0);
        $estimatedUnitPrice = round($pieceAreaM2 * $unitPriceM2, 0);
        $estimatedTotal = round($floorAreaM2 * $unitPriceM2, 0);

        $quotation = [
            'scene_name' => (string) ($validated['scene_name'] ?? 'Escena 3D personalizada'),
            'generated_at' => now(),
            'currency' => (string) config('three_quotation.currency', 'BOB'),
            'currency_symbol' => (string) config('three_quotation.currency_symbol', 'Bs'),
            'prices_are_reference' => (bool) config('three_quotation.prices_are_reference', true),
            'floor_kind' => $this->floorLabel((string) ($validated['floor_kind'] ?? 'custom')),
            'room' => $room,
            'piece' => $piece,
            'summary' => [
                'floor_area_m2' => $floorAreaM2,
                'piece_area_m2' => $pieceAreaM2,
                'estimated_units' => $estimatedUnits,
                'unit_price_m2' => $unitPriceM2,
                'estimated_unit_price' => $estimatedUnitPrice,
                'estimated_total' => $estimatedTotal,
            ],
            'plan_svg' => $this->buildPlanSvg($room, $piece),
        ];

        $pdf = Pdf::loadView('three.quotation', [
            'quotation' => $quotation,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('cotizacion-escena-3d-'.now()->format('Ymd_His').'.pdf');
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
