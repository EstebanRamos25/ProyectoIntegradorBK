<?php

namespace App\Orchid\Resources;

use App\Models\Factura;
use Orchid\Crud\Resource;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class FacturaResource extends Resource
{
    public static $model = Factura::class;

    /**
     * Campos del formulario (solo lectura — las facturas no se editan manualmente).
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Columnas del listado.
     */
    public function columns(): array
    {
        return [
            TD::make('Factura', '')
                ->render(function (Factura $factura) {
                    $num     = e($factura->numero_factura);
                    $fecha   = $factura->fecha_emision?->format('d/m/Y') ?? '-';
                    $cliente = e($factura->nombre_cliente);
                    $total   = number_format($factura->total, 2);
                    $iva     = number_format($factura->iva_monto, 2);

                    $estadoColor = $factura->estado === 'emitida'
                        ? 'background:#d1fae5; color:#065f46;'
                        : 'background:#fee2e2; color:#991b1b;';

                    $pdfUrl = route('facturas.pdf', $factura->id);

                    return "
                    <div style='display:flex; justify-content:space-between; align-items:center; width:100%;'>

                        <div style='display:flex; gap:14px; align-items:center;'>
                            <div style='width:44px; height:44px; border-radius:10px; background:#f0fdf4;
                                        color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:20px;'>
                                <i class='bs.receipt'></i>
                            </div>
                            <div style='display:flex; flex-direction:column; gap:3px;'>
                                <div style='display:flex; align-items:center; gap:8px;'>
                                    <strong style='font-size:15px; color:#0f172a;'>{$num}</strong>
                                    <span style='{$estadoColor} padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600;'>
                                        " . strtoupper($factura->estado) . "
                                    </span>
                                </div>
                                <div style='font-size:12px; color:#64748b;'>
                                    <span><i class='bs.person'></i> {$cliente}</span>
                                    &nbsp;&bull;&nbsp;
                                    <span><i class='bs.calendar'></i> {$fecha}</span>
                                </div>
                                <div style='font-size:11px; color:#94a3b8; font-family:monospace;'>
                                    Auth: {$factura->codigo_autorizacion}
                                </div>
                            </div>
                        </div>

                        <div style='display:flex; flex-direction:column; align-items:flex-end; gap:4px;'>
                            <div style='font-size:11px; color:#94a3b8;'>IVA: Bs {$iva}</div>
                            <strong style='font-size:18px; color:#10b981; font-weight:800;'>Bs {$total}</strong>
                            <a href='{$pdfUrl}' target='_blank'
                               style='font-size:11px; color:#4f46e5; text-decoration:none;'>
                               <i class='bs.file-earmark-pdf'></i> Descargar PDF
                            </a>
                        </div>
                    </div>";
                }),
        ];
    }

    /**
     * Detalle de una factura.
     */
    public function legend(): array
    {
        return [
            Sight::make('numero_factura',    'N° Factura'),
            Sight::make('fecha_emision',     'Fecha de Emisión'),
            Sight::make('nit_emisor',        'NIT Emisor'),
            Sight::make('razon_social_emisor','Razón Social Emisor'),
            Sight::make('nit_cliente',       'NIT / CI Cliente'),
            Sight::make('nombre_cliente',    'Cliente'),
            Sight::make('subtotal_sin_iva',  'Subtotal (sin IVA)'),
            Sight::make('iva_monto',         'IVA 13%'),
            Sight::make('total',             'TOTAL'),
            Sight::make('estado',            'Estado'),
            Sight::make('codigo_autorizacion','Código de Autorización'),
            Sight::make('codigo_control',    'Código de Control'),
            Sight::make('venta.id',          'Venta N°'),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    /**
     * Las facturas se crean exclusivamente desde el módulo de Ventas.
     * Este método bloquea la creación directa con validación.
     */
    public function rules(\Illuminate\Database\Eloquent\Model $model): array
    {
        // Bloquear creación directa desde el panel: venta_id es obligatorio.
        // Las facturas se generan exclusivamente desde el módulo Ventas.
        return [
            'venta_id' => ['required', 'exists:ventas,id'],
        ];
    }

    public static function createButtonLabel(): string
    {
        // Retornar string vacío oculta visualmente el botón en Orchid CRUD
        return '';
    }

    public static function deleteButtonLabel(): string
    {
        return '';
    }
}
