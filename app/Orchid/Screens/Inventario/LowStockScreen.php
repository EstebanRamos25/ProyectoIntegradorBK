<?php

namespace App\Orchid\Screens\Inventario;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Venta;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class LowStockScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        // 1. Calculate stock per product
        $stockAgg = Inventario::query()
            ->selectRaw('producto_id, SUM(COALESCE(Cajas_Disponibles, Cantidad, 0)) as total_stock')
            ->groupBy('producto_id')
            ->get();

        // Filter products with stock <= 10
        $lowStockProductsIds = $stockAgg->where('total_stock', '<=', 10)->pluck('producto_id');

        // Total counters
        $outOfStockCount = $stockAgg->where('total_stock', '<=', 0)->count();
        $lowStockCount = $stockAgg->where('total_stock', '>', 0)->where('total_stock', '<=', 10)->count();

        // Fetch products
        $products = Producto::query()
            ->whereIn('id', $lowStockProductsIds)
            ->get();

        // Fetch sales to calculate lifetime sales
        $salesAgg = Venta::query()
            ->whereIn('producto_id', $lowStockProductsIds)
            ->selectRaw('producto_id, SUM(Area_M2) as total_m2, COUNT(*) as count_ventas')
            ->groupBy('producto_id')
            ->get()
            ->keyBy('producto_id');

        $rows = [];
        foreach ($products as $p) {
            $stock = $stockAgg->firstWhere('producto_id', $p->id)->total_stock ?? 0;
            $sales = $salesAgg->get($p->id);
            $ventas_count = $sales->count_ventas ?? 0;
            $m2_sold = $sales->total_m2 ?? 0;

            $rows[] = (object) [
                'id' => $p->id,
                'nombre' => $p->Nombre,
                'stock' => $stock,
                'ventas_historicas' => $ventas_count,
                'm2_vendidos' => $m2_sold,
                'estado' => $stock <= 0 ? 'Agotado' : 'Bajo Stock',
            ];
        }
        
        // Sort by stock ascending
        usort($rows, function($a, $b) {
            return $a->stock <=> $b->stock;
        });

        return [
            'metrics' => [
                'Agotados (Stock 0)' => ['value' => number_format($outOfStockCount)],
                'Bajo Stock (1 a 10 cajas)' => ['value' => number_format($lowStockCount)],
            ],
            'productos' => $rows,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Alertas de Stock (Inventario Crítico)';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Agotados (Stock 0)' => 'metrics.Agotados (Stock 0)',
                'Bajo Stock (1 a 10 cajas)' => 'metrics.Bajo Stock (1 a 10 cajas)',
            ]),

            Layout::table('productos', [
                TD::make('nombre', 'Producto')->render(fn($row) => "<strong style='font-size:14px; color:#0f172a;'>{$row->nombre}</strong>"),
                TD::make('estado', 'Estado')->render(function($row) {
                    if ($row->stock <= 0) {
                        return "<span style='background:#fef2f2;color:#ef4444;padding:4px 8px;border-radius:4px;font-weight:600;font-size:11px;letter-spacing:0.5px;'>AGOTADO</span>";
                    }
                    return "<span style='background:#fffbeb;color:#d97706;padding:4px 8px;border-radius:4px;font-weight:600;font-size:11px;letter-spacing:0.5px;'>BAJO STOCK</span>";
                }),
                TD::make('stock', 'Cajas Disponibles')->alignRight()->render(fn($row) => "<span style='font-size:20px;font-weight:800;color:".($row->stock <= 0 ? '#ef4444' : '#d97706')."'>{$row->stock}</span>"),
                TD::make('ventas_historicas', 'Rendimiento (Vida Útil)')->alignRight()->render(function($row) {
                    $m2 = number_format((float)$row->m2_vendidos, 2);
                    return "
                        <div style='display:flex;flex-direction:column;align-items:flex-end;'>
                            <strong style='font-size:14px;color:#10b981;'>{$row->ventas_historicas} Ventas Confirmadas</strong>
                            <span style='color:#64748b;font-size:12px;'>Total Histórico: {$m2} m²</span>
                        </div>
                    ";
                }),
            ]),
        ];
    }
}
