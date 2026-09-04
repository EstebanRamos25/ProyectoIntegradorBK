<?php

namespace App\Orchid\Resources;

use App\Models\Venta;
use App\Models\User;
use App\Models\Promocion;
use App\Models\Inventario;
use App\Models\Producto;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class VentaResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Venta::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('Total')
                ->type('number')
                ->step(0.01)
                ->title('Total')
                ->placeholder('Ingresa el total de la venta'),

            DateTimer::make('Fecha')
                ->title('Fecha')
                ->placeholder('Selecciona la fecha de la venta'),

            // Selector para usuario relacionado
            Select::make('usuario_id')
                ->title('Usuario')
                ->fromModel(User::class, 'name')
                ->empty('Selecciona un usuario'),

            // Selector para promoción relacionada
            Select::make('promocion_id')
                ->title('Promoción')
                ->fromModel(Promocion::class, 'Nombre')
                ->empty('Selecciona una promoción'),

            // El campo inventario_id es legacy. Las ventas ahora registran el detalle
            // de inventarios múltiples en VentaInventario.

            Select::make('producto_id')
                ->title('Producto')
                ->fromModel(Producto::class, 'Nombre')
                ->empty('Selecciona un producto'),
        ];
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('Detalles', '')
                ->render(function ($model) {
                    $id      = $model->id;
                    $origen  = e($model->Origen ?? 'Tienda');
                    $fecha   = $model->Fecha ? date('d M Y', strtotime($model->Fecha)) : '-';
                    $cliente = e(optional($model->usuario)->name ?? 'Cliente General');
                    $producto = e(optional($model->producto)->Nombre ?? 'Varios');
                    $m2      = $model->Area_M2 ?? 0;

                    $subtotal = number_format((float)$model->Subtotal, 2);
                    $desc     = number_format((float)$model->Descuento_Pct, 0);
                    $total    = number_format((float)$model->Total, 2);

                    // ── Bloque de factura (cargado con loadMissing para evitar N+1 silencioso) ──
                    $model->loadMissing('factura');
                    $factura = $model->factura;

                    if ($factura) {
                        $numF   = e($factura->numero_factura);
                        $pdfUrl = route('facturas.pdf', $factura->id);
                        $estilo = $factura->estado === 'emitida'
                            ? 'color:#065f46; background:#d1fae5;'
                            : 'color:#991b1b; background:#fee2e2;';
                        $estadoTxt = strtoupper($factura->estado);

                        $facturaHtml = "
                        <div style='display:flex; flex-direction:column; align-items:flex-end; gap:3px; margin-top:6px;'>
                            <span style='{$estilo} padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600;'>
                                {$estadoTxt}
                            </span>
                            <span style='font-size:12px; font-weight:700; color:#1e293b;'>{$numF}</span>
                            <a href='{$pdfUrl}' target='_blank'
                               style='font-size:11px; color:#4f46e5; text-decoration:none;'>
                               &#128196; Ver Factura PDF
                            </a>
                        </div>";
                    } else {
                        $generateUrl = route('facturas.generate', $model->id);
                        $csrf        = csrf_token();
                        $facturaHtml = "
                        <form method='POST' action='{$generateUrl}' data-turbo='false' style='margin:0; margin-top:6px;'>
                            <input type='hidden' name='_token' value='{$csrf}'>
                            <button type='submit'
                                style='background:#4f46e5; color:#fff; border:none; border-radius:8px;
                                       padding:5px 12px; font-size:11px; font-weight:600; cursor:pointer;'
                                onclick=\"return confirm('Generar factura para la venta #{$id}?')\">
                                &#9654; Generar Factura
                            </button>
                        </form>";
                    }

                    return "
                    <div style='display:flex; justify-content:space-between; align-items:flex-start; width:100%;'>

                        <div style='display:flex; gap:16px; align-items:center;'>
                            <div style='width:48px; height:48px; border-radius:12px; background:#eef2ff;
                                        color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:20px;'>
                                &#9654;
                            </div>
                            <div style='display:flex; flex-direction:column; gap:4px;'>
                                <div style='display:flex; align-items:center; gap:8px;'>
                                    <strong style='font-size:16px; color:#0f172a;'>#{$id} - {$cliente}</strong>
                                    <span style='background:#f1f5f9; color:#475569; font-size:11px;
                                                 padding:2px 8px; border-radius:12px; font-weight:600;'>{$origen}</span>
                                </div>
                                <div style='font-size:13px; color:#64748b; display:flex; gap:12px;'>
                                    <span>&#128197; {$fecha}</span>
                                    <span>&#128230; {$producto} ({$m2} m&sup2;)</span>
                                </div>
                            </div>
                        </div>

                        <div style='display:flex; flex-direction:column; align-items:flex-end; gap:2px;'>
                            <div style='font-size:12px; color:#94a3b8;'>Subtotal: Bs {$subtotal}
                                <span style='color:#ef4444;'>( -{$desc}% )</span>
                            </div>
                            <strong style='font-size:20px; color:#10b981; font-weight:800;'>Bs {$total}</strong>
                            {$facturaHtml}
                        </div>

                    </div>";
                }),
        ];
    }

    /**
     * Get the sights displayed by the resource.
     *
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('Total', 'TOTAL'),
            Sight::make('Fecha', 'FECHA'),
            Sight::make('Origen', 'ORIGEN'),
            Sight::make('Area_M2', 'M²'),
            Sight::make('Precio_M2', 'PRECIO/M²'),
            Sight::make('Subtotal', 'SUBTOTAL'),
            Sight::make('Descuento_Pct', 'DESC %'),
            Sight::make('Descuento_Monto', 'DESC MONTO'),
            Sight::make('Costo_M2', 'COSTO/M²'),
            Sight::make('Costo_Total', 'COSTO TOTAL'),
            Sight::make('Ganancia', 'GANANCIA'),
            Sight::make('usuario.name', 'USUARIO'),
            Sight::make('promocion.Nombre', 'PROMOCIÓN'),
            Sight::make('inventario.id', 'INVENTARIO'),
            Sight::make('producto.Nombre', 'PRODUCTO'),
            Sight::make('created_at', 'FECHA DE CREACIÓN'),
            Sight::make('updated_at', 'FECHA DE ACTUALIZACIÓN'),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }
}
