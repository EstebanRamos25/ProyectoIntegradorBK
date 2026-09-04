<?php

namespace App\Orchid\Screens\Recommender;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Venta;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Link;
use Barryvdh\DomPDF\Facade\Pdf;

class MLDecisionReportScreen extends Screen
{
    public function query(): iterable
    {
        // 1. Obtener puntuación de tendencia (Ventas en últimos 60 días)
        $since = now()->subDays(60)->toDateString();
        $salesAgg = Venta::query()
            ->where('Fecha', '>=', $since)
            ->whereNotNull('producto_id')
            ->selectRaw('producto_id, COUNT(*) as c')
            ->groupBy('producto_id')
            ->get()
            ->keyBy('producto_id');

        // 2. Obtener Stock
        $stockAgg = Inventario::query()
            ->selectRaw('producto_id, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as total_stock')
            ->groupBy('producto_id')
            ->get()
            ->keyBy('producto_id');

        // 3. Procesar Productos
        $products = Producto::with('categoria')->get();

        $rows = [];
        $estrellaCount = 0;
        $reabastecerCount = 0;
        $liquidarCount = 0;

        // Definir umbral de Alta Tendencia
        $allSalesCount = $salesAgg->pluck('c')->toArray();
        rsort($allSalesCount);
        $topQuartileIndex = max(0, (int) floor(count($allSalesCount) * 0.25) - 1);
        $altaTendenciaThreshold = $allSalesCount[$topQuartileIndex] ?? 0;
        $altaTendenciaThreshold = max(2, $altaTendenciaThreshold); 

        foreach ($products as $p) {
            $stock = (int) ($stockAgg->get($p->id)->total_stock ?? 0);
            $sales = (int) ($salesAgg->get($p->id)->c ?? 0);
            $score = log(1 + $sales); // Score de tendencia simplificado

            $altaTendencia = $sales >= $altaTendenciaThreshold;
            $bajoStock = $stock <= 15;

            $decision = 'Neutro';
            $action = 'Mantener';
            
            if ($altaTendencia && !$bajoStock) {
                $decision = 'Estrella';
                $action = 'Promocionar';
                $estrellaCount++;
            } elseif ($altaTendencia && $bajoStock) {
                $decision = 'Riesgo de Quiebre';
                $action = 'Reabastecer';
                $reabastecerCount++;
            } elseif (!$altaTendencia && !$bajoStock && $stock > 50) { // Mucho stock, sin ventas
                $decision = 'Exceso (Estancado)';
                $action = 'Liquidar / Descuento';
                $liquidarCount++;
            }

            // Excluir productos agotados y neutros para limpiar la vista
            if ($decision === 'Neutro' && $stock <= 0) continue;

            $rows[] = (object) [
                'id' => $p->id,
                'nombre' => $p->Nombre,
                'categoria' => $p->categoria->Nombre ?? '-',
                'stock' => $stock,
                'tendencia' => $sales,
                'score' => round($score, 2),
                'decision' => $decision,
                'accion' => $action
            ];
        }

        // Ordenar por score de mayor a menor
        usort($rows, fn($a, $b) => $b->score <=> $a->score);

        return [
            'metrics' => [
                'Estrellas (Promocionar)' => ['value' => number_format($estrellaCount)],
                'Riesgos (Reabastecer)' => ['value' => number_format($reabastecerCount)],
                'Excesos (Liquidar)' => ['value' => number_format($liquidarCount)],
            ],
            'productos' => $rows,
            '_allRows' => $rows // Guardamos todo para el PDF si fuera necesario exportar en la misma vista (pero usaremos un controller simple)
        ];
    }

    public function name(): ?string
    {
        return 'Decisiones y Tendencias (ML)';
    }

    public function description(): ?string
    {
        return 'Reporte inteligente cruzando el modelo de recomendaciones (Demanda) vs Inventario (Oferta).';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Descargar Reporte PDF')
                ->icon('bs.file-pdf')
                ->route('platform.decisiones.report')
                ->target('_blank'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Estrellas (Promocionar)' => 'metrics.Estrellas (Promocionar)',
                'Riesgos (Reabastecer)' => 'metrics.Riesgos (Reabastecer)',
                'Excesos (Liquidar)' => 'metrics.Excesos (Liquidar)',
            ]),

            Layout::table('productos', [
                TD::make('nombre', 'Producto')->render(fn($row) => "<strong style='font-size:14px;color:#0f172a;'>{$row->nombre}</strong><br><span style='font-size:11px;color:#64748b;'>{$row->categoria}</span>"),
                TD::make('score', 'Demanda (Tendencia)')->alignRight()->render(fn($row) => "
                    <div style='display:flex;align-items:center;justify-content:flex-end;gap:6px;'>
                        <span style='font-size:16px;font-weight:700;color:#4f46e5;'>{$row->score}</span>
                        <i class='bs.graph-up-arrow' style='color:#6366f1;font-size:12px;'></i>
                    </div>
                "),
                TD::make('stock', 'Stock Físico (Cajas)')->alignRight()->render(fn($row) => "<span style='font-size:15px;font-weight:600;'>{$row->stock}</span>"),
                TD::make('decision', 'Diagnóstico')->render(function($row) {
                    if ($row->decision === 'Estrella') {
                        return "<span style='background:#f0fdf4;color:#16a34a;padding:4px 8px;border-radius:12px;font-weight:600;font-size:11px;text-transform:uppercase;'>⭐ {$row->decision}</span>";
                    } elseif ($row->decision === 'Riesgo de Quiebre') {
                        return "<span style='background:#fef2f2;color:#ef4444;padding:4px 8px;border-radius:12px;font-weight:600;font-size:11px;text-transform:uppercase;'>🔥 {$row->decision}</span>";
                    } elseif ($row->decision === 'Exceso (Estancado)') {
                        return "<span style='background:#fffbeb;color:#d97706;padding:4px 8px;border-radius:12px;font-weight:600;font-size:11px;text-transform:uppercase;'>🧊 {$row->decision}</span>";
                    }
                    return "<span style='background:#f1f5f9;color:#64748b;padding:4px 8px;border-radius:12px;font-weight:600;font-size:11px;text-transform:uppercase;'>{$row->decision}</span>";
                }),
                TD::make('accion', 'Decisión Sugerida')->render(function($row) {
                    if ($row->accion === 'Promocionar') {
                        return "<strong style='color:#16a34a;font-size:13px;'>Destacar en portada</strong>";
                    } elseif ($row->accion === 'Reabastecer') {
                        return "<strong style='color:#ef4444;font-size:13px;'>Re-stock Urgente</strong>";
                    } elseif ($row->accion === 'Liquidar / Descuento') {
                        return "<strong style='color:#d97706;font-size:13px;'>Aplicar Descuento</strong>";
                    }
                    return "<span style='color:#64748b;font-size:13px;'>Mantener estatus</span>";
                }),
            ]),
        ];
    }
    
    // Función para el reporte en PDF
    public function export()
    {
        $data = $this->query();
        $rows = $data['_allRows'] ?? [];
        
        $pdf = Pdf::loadView('orchid.decisiones.report', [
            'rows' => $rows,
            'generatedAt' => now()
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_decisiones_inteligentes.pdf');
    }
}
