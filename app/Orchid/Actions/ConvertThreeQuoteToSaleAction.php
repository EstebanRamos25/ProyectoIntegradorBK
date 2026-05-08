<?php

declare(strict_types=1);

namespace App\Orchid\Actions;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ThreeQuote;
use App\Models\Venta;
use App\Models\VentaInventario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Orchid\Crud\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;

class ConvertThreeQuoteToSaleAction extends Action
{
    public function button(): Button
    {
        return Button::make('Convertir a venta')
            ->icon('bs.cart-check')
            ->canSee(false);
    }

    public function handle(Collection $models)
    {
        $adminId = Auth::id();

        $converted = 0;
        $skipped = 0;

        foreach ($models as $model) {
            /** @var ThreeQuote $quote */
            $quote = $model instanceof ThreeQuote
                ? $model
                : ThreeQuote::query()->find($model->id);

            if (!$quote) {
                $skipped++;
                continue;
            }

            if ($quote->status !== 'sent') {
                $skipped++;
                continue;
            }

            $productoId = (int) ($quote->producto_id ?? data_get($quote->quotation, 'material.id') ?? 0);
            $boxesRequired = (int) ($quote->boxes_required ?? data_get($quote->quotation, 'summary.boxes_required') ?? 0);

            if ($productoId <= 0 || $boxesRequired <= 0) {
                $skipped++;
                continue;
            }

            /** @var ?Producto $producto */
            $producto = Producto::query()->find($productoId);
            if (!$producto) {
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($quote, $productoId, $boxesRequired, $producto, $adminId): void {
                    $lockedQuote = ThreeQuote::query()
                        ->whereKey((int) $quote->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedQuote->status !== 'sent') {
                        throw new \RuntimeException('La cotización ya no está en estado enviado.');
                    }

                    // Re-validación de stock y descuento por lotes (FIFO por fecha)
                    $inventarios = Inventario::query()
                        ->where('producto_id', $productoId)
                        ->orderByRaw('Fecha_Ingreso is null')
                        ->orderBy('Fecha_Ingreso')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = 0;
                    foreach ($inventarios as $inv) {
                        $totalAvailable += (int) ($inv->Cajas_Disponibles ?? $inv->Cantidad ?? 0);
                    }

                    if ($totalAvailable < $boxesRequired) {
                        throw new \RuntimeException('Stock insuficiente para convertir a venta.');
                    }

                    $remaining = $boxesRequired;
                    $firstInventarioId = null;
                    $takes = [];

                    foreach ($inventarios as $inv) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $available = (int) ($inv->Cajas_Disponibles ?? $inv->Cantidad ?? 0);
                        if ($available <= 0) {
                            continue;
                        }

                        $take = min($available, $remaining);
                        if ($take <= 0) {
                            continue;
                        }

                        if ($firstInventarioId === null) {
                            $firstInventarioId = (int) $inv->id;
                        }

                        if ($inv->Cajas_Disponibles !== null) {
                            $inv->Cajas_Disponibles = max(0, (int) $inv->Cajas_Disponibles - $take);
                        } else {
                            $inv->Cantidad = max(0, (int) ($inv->Cantidad ?? 0) - $take);
                        }

                        $inv->save();

                        $takes[(int) $inv->id] = ($takes[(int) $inv->id] ?? 0) + $take;

                        $remaining -= $take;
                    }

                    if ($remaining > 0) {
                        throw new \RuntimeException('No se pudo asignar stock por lotes.');
                    }

                    $areaM2 = (float) ($lockedQuote->area_m2 ?? data_get($lockedQuote->quotation, 'summary.floor_area_m2') ?? 0);
                    $unitPriceM2 = (float) (data_get($lockedQuote->quotation, 'summary.unit_price_m2') ?? 0);
                    $subtotal = (float) (data_get($lockedQuote->quotation, 'summary.subtotal') ?? data_get($lockedQuote->quotation, 'summary.estimated_total') ?? 0);
                    $discountPct = data_get($lockedQuote->quotation, 'summary.discount_pct');
                    $discountAmount = data_get($lockedQuote->quotation, 'summary.discount_amount');
                    $total = (float) ($lockedQuote->total ?? data_get($lockedQuote->quotation, 'summary.total_after_discount') ?? 0);

                    $promocionId = data_get($lockedQuote->quotation, 'promotion.id');
                    $promocionId = $promocionId ? (int) $promocionId : null;

                    $costoM2 = $producto->Costo_M2 !== null ? (float) $producto->Costo_M2 : null;
                    $costoTotal = $costoM2 !== null ? round($areaM2 * $costoM2, 0) : null;
                    $ganancia = $costoTotal !== null ? round($total - $costoTotal, 0) : null;

                    $venta = Venta::query()->create([
                        'Total' => $total,
                        'Fecha' => now()->toDateString(),
                        'Origen' => '3d_sale',
                        'three_quote_id' => $lockedQuote->id,
                        'usuario_id' => (int) $lockedQuote->user_id,
                        'promocion_id' => $promocionId,
                        'inventario_id' => $firstInventarioId,
                        'producto_id' => $productoId,
                        'Area_M2' => $areaM2 > 0 ? $areaM2 : null,
                        'Precio_M2' => $unitPriceM2 > 0 ? $unitPriceM2 : null,
                        'Subtotal' => $subtotal > 0 ? $subtotal : null,
                        'Descuento_Pct' => $discountPct !== null ? (float) $discountPct : null,
                        'Descuento_Monto' => $discountAmount !== null ? (float) $discountAmount : null,
                        'Costo_M2' => $costoM2,
                        'Costo_Total' => $costoTotal,
                        'Ganancia' => $ganancia,
                    ]);

                    foreach ($takes as $inventarioId => $take) {
                        if ((int) $take <= 0) {
                            continue;
                        }

                        VentaInventario::query()->create([
                            'venta_id' => (int) $venta->id,
                            'inventario_id' => (int) $inventarioId,
                            'cajas_descontadas' => (int) $take,
                        ]);
                    }

                    $lockedQuote->status = 'sold';
                    $lockedQuote->sold_at = now();
                    $lockedQuote->sold_by_user_id = $adminId ? (int) $adminId : null;
                    $lockedQuote->venta_id = (int) $venta->id;
                    $lockedQuote->save();
                });

                $converted++;
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }
        }

        if ($converted > 0) {
            Toast::success("Ventas creadas: {$converted}");
        }
        if ($skipped > 0) {
            Toast::warning("Omitidas: {$skipped} (verifica estado y stock)");
        }

        return back();
    }
}
