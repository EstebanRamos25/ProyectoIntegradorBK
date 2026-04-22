<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Ventas;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Orchid\Screen\Actions\Button;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class VentasProfitReportScreen extends Screen
{
    public $name = 'Reporte de ganancias';

    public $description = 'Ganancia estimada por cotización/venta generada desde la experiencia 3D.';

    public function permission(): ?iterable
    {
        return [
            'platform.ventas.report',
        ];
    }

    public function query(): iterable
    {
        $from = $this->parseDate(request('from'));
        $to = $this->parseDate(request('to'));

        $ventasQuery = Venta::query()
            ->with(['usuario:id,name', 'producto:id,Nombre', 'promocion:id,Nombre,Descuento,Min_M2'])
            ->where('Origen', '3d_quotation')
            ->orderByDesc('created_at');

        if ($from) {
            $ventasQuery->whereDate('Fecha', '>=', $from->toDateString());
        }

        if ($to) {
            $ventasQuery->whereDate('Fecha', '<=', $to->toDateString());
        }

        $ventas = $ventasQuery->limit(200)->get();

        return [
            'ventas' => $ventas,
            'metrics' => $this->buildMetrics($ventas),
            'filters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Exportar PDF')
                ->icon('bs.download')
                ->href(route('platform.ventas.report.export', [
                    'from' => request('from'),
                    'to' => request('to'),
                ]))
                ->target('_blank'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                DateTimer::make('from')
                    ->title('Desde')
                    ->allowInput()
                    ->format('Y-m-d')
                    ->value(request('from')),
                DateTimer::make('to')
                    ->title('Hasta')
                    ->allowInput()
                    ->format('Y-m-d')
                    ->value(request('to')),
                Button::make('Aplicar filtro')
                    ->icon('bs.filter')
                    ->method('applyFilter'),
            ])->title('Filtrar por fecha'),

            Layout::metrics([
                'Registros (3D)' => 'metrics.count',
                'Total neto' => 'metrics.total',
                'Costo total' => 'metrics.cost',
                'Ganancia' => 'metrics.profit',
            ]),

            Layout::table('ventas', [
                TD::make('Fecha', 'FECHA')->render(fn (Venta $v) => (string) ($v->Fecha ?: $v->created_at?->toDateString() ?: '—')),
                TD::make('producto.Nombre', 'PRODUCTO')->render(fn (Venta $v) => (string) (optional($v->producto)->Nombre ?: '—')),
                TD::make('Area_M2', 'M²')->render(fn (Venta $v) => $v->Area_M2 !== null ? number_format((float) $v->Area_M2, 2, ',', '.') : '—'),
                TD::make('promocion.Nombre', 'PROMOCIÓN')->render(function (Venta $v) {
                    $p = $v->promocion;
                    if (!$p) return '—';
                    $min = $p->Min_M2 !== null ? number_format((float) $p->Min_M2, 2, ',', '.') : null;
                    $desc = $p->Descuento !== null ? number_format((float) $p->Descuento, 2, ',', '.') : null;
                    $extra = trim(collect([
                        $min ? ('min ' . $min . ' m²') : null,
                        $desc ? ($desc . '%') : null,
                    ])->filter()->implode(' · '));

                    return $extra ? ($p->Nombre . ' (' . $extra . ')') : (string) $p->Nombre;
                }),
                TD::make('Total', 'TOTAL')->alignRight()->render(fn (Venta $v) => $v->Total !== null ? ('Bs ' . number_format((float) $v->Total, 0, ',', '.')) : '—'),
                TD::make('Costo_Total', 'COSTO')->alignRight()->render(fn (Venta $v) => $v->Costo_Total !== null ? ('Bs ' . number_format((float) $v->Costo_Total, 0, ',', '.')) : '—'),
                TD::make('Ganancia', 'GANANCIA')->alignRight()->render(fn (Venta $v) => $v->Ganancia !== null ? ('Bs ' . number_format((float) $v->Ganancia, 0, ',', '.')) : '—'),
            ]),
        ];
    }

    public function export()
    {
        $from = $this->parseDate(request('from'));
        $to = $this->parseDate(request('to'));

        $ventasQuery = Venta::query()
            ->with(['usuario:id,name', 'producto:id,Nombre', 'promocion:id,Nombre,Descuento,Min_M2'])
            ->where('Origen', '3d_quotation')
            ->orderByDesc('created_at');

        if ($from) {
            $ventasQuery->whereDate('Fecha', '>=', $from->toDateString());
        }

        if ($to) {
            $ventasQuery->whereDate('Fecha', '<=', $to->toDateString());
        }

        $ventas = $ventasQuery->get();

        $filename = 'reporte-ganancias-' . now()->format('Ymd_His') . '.pdf';

        $summary = $this->buildMetrics($ventas);

        $pdf = Pdf::loadView('orchid.ventas.report', [
            'generatedAt' => now(),
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => $summary,
            'rows' => $this->buildRows($ventas),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($filename);
    }

    public function applyFilter(): RedirectResponse
    {
        return redirect()->route('platform.ventas.report', [
            'from' => request('from'),
            'to' => request('to'),
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildMetrics(Collection $ventas): array
    {
        $count = (int) $ventas->count();
        $total = (float) $ventas->sum(fn (Venta $v) => (float) ($v->Total ?? 0));
        $cost = (float) $ventas->sum(fn (Venta $v) => (float) ($v->Costo_Total ?? 0));
        $profit = (float) $ventas->sum(fn (Venta $v) => (float) ($v->Ganancia ?? 0));

        return [
            'count' => ['value' => number_format($count, 0, ',', '.'), 'diff' => 0],
            'total' => ['value' => 'Bs ' . number_format($total, 0, ',', '.'), 'diff' => 0],
            'cost' => ['value' => 'Bs ' . number_format($cost, 0, ',', '.'), 'diff' => 0],
            'profit' => ['value' => 'Bs ' . number_format($profit, 0, ',', '.'), 'diff' => 0],
        ];
    }

    private function buildRows(Collection $ventas): array
    {
        return $ventas->map(function (Venta $v): array {
            return [
                'fecha' => (string) ($v->Fecha ?: $v->created_at?->toDateString() ?: '—'),
                'usuario' => (string) (optional($v->usuario)->name ?: '—'),
                'producto' => (string) (optional($v->producto)->Nombre ?: '—'),
                'promocion' => (string) (optional($v->promocion)->Nombre ?: '—'),
                'area_m2' => $v->Area_M2 !== null ? (float) $v->Area_M2 : null,
                'precio_m2' => $v->Precio_M2 !== null ? (float) $v->Precio_M2 : null,
                'subtotal' => $v->Subtotal !== null ? (float) $v->Subtotal : null,
                'descuento_pct' => $v->Descuento_Pct !== null ? (float) $v->Descuento_Pct : null,
                'descuento_monto' => $v->Descuento_Monto !== null ? (float) $v->Descuento_Monto : null,
                'total' => $v->Total !== null ? (float) $v->Total : null,
                'costo_total' => $v->Costo_Total !== null ? (float) $v->Costo_Total : null,
                'ganancia' => $v->Ganancia !== null ? (float) $v->Ganancia : null,
            ];
        })->all();
    }
}
