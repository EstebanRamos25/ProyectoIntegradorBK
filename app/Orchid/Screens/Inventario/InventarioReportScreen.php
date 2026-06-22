<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Inventario;

use App\Models\Inventario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class InventarioReportScreen
{
    public function export()
    {
        $inventarios = Inventario::query()
            ->with('producto')
            ->orderBy('producto_id')
            ->orderBy('Codigo_Lote')
            ->orderBy('id')
            ->get();

        $filename = 'reporte-inventarios-' . now()->format('Ymd_His') . '.pdf';

        $summary = $this->buildSummary($inventarios);

        $pdf = Pdf::loadView('orchid.inventario.report', [
            'generatedAt' => now(),
            'summary' => $summary,
            'topProducts' => $summary['topProducts'] ?? [],
            'rows' => $this->buildRows($inventarios),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($filename);
    }

    protected function buildSummary(Collection $inventarios): array
    {
        $totalCajasEntrada = (int) $inventarios->sum(fn ($i) => (int) ($i->Cajas_Entrada ?? 0));
        $totalCajasDisponibles = (int) $inventarios->sum(fn ($i) => (int) ($i->Cajas_Disponibles ?? 0));
        $totalLegacy = (int) $inventarios->sum(fn ($i) => (int) ($i->Cantidad ?? 0));

        $productosUnicos = $inventarios
            ->pluck('producto_id')
            ->filter(fn ($id) => !empty($id))
            ->unique()
            ->count();

        $sinDisponibles = $inventarios
            ->filter(function ($i): bool {
                $disponibles = (int) ($i->Cajas_Disponibles ?? 0);
                $legacy = (int) ($i->Cantidad ?? 0);

                return $disponibles <= 0 && $legacy <= 0;
            })
            ->count();

        $topProducts = $inventarios
            ->groupBy(fn ($i) => (string) (optional($i->producto)->Nombre ?: 'Sin producto'))
            ->map(fn (Collection $g) => (int) $g->sum(fn ($i) => (int) ($i->Cajas_Disponibles ?? 0)))
            ->sortDesc()
            ->take(8)
            ->all();

        $rangeFrom = $inventarios->min(function ($i) {
            return $i->Fecha_Ingreso ?: $i->created_at;
        });

        $rangeTo = $inventarios->max(function ($i) {
            return $i->Fecha_Ingreso ?: $i->created_at;
        });

        return [
            'totalRegistros' => $inventarios->count(),
            'productosUnicos' => $productosUnicos,
            'totalCajasEntrada' => $totalCajasEntrada,
            'totalCajasDisponibles' => $totalCajasDisponibles,
            'totalLegacyCantidad' => $totalLegacy,
            'sinDisponibles' => $sinDisponibles,
            'topProducts' => $topProducts,
            'range' => [
                'from' => $rangeFrom,
                'to' => $rangeTo,
            ],
        ];
    }

    protected function buildRows(Collection $inventarios): array
    {
        return $inventarios
            ->map(function ($i): array {
                return [
                    'id' => (string) $i->id,
                    'producto' => (string) (optional($i->producto)->Nombre ?: '—'),
                    'lote' => (string) ($i->Codigo_Lote ?: '—'),
                    'tono' => (string) ($i->Tono ?: '—'),
                    'calibre' => (string) ($i->Calibre ?: '—'),
                    'cajas_disponibles' => (int) ($i->Cajas_Disponibles ?? 0),
                    'cajas_entrada' => (int) ($i->Cajas_Entrada ?? 0),
                    'costo_m2' => $i->Costo_M2 !== null ? (float) $i->Costo_M2 : null,
                    'legacy_cantidad' => (int) ($i->Cantidad ?? 0),
                    'ubicacion' => (string) ($i->Ubicacion ?: '—'),
                    'estado' => (string) ($i->Estado ?: '—'),
                    'fecha_ingreso' => $i->Fecha_Ingreso ? (string) $i->Fecha_Ingreso : '—',
                    'updated_at' => $i->updated_at ? $i->updated_at->format('d/m/Y H:i') : '—',
                ];
            })
            ->all();
    }
}
