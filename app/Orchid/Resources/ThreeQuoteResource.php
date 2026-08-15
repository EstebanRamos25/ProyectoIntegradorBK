<?php

namespace App\Orchid\Resources;

use App\Models\ThreeQuote;
use App\Orchid\Actions\ConvertThreeQuoteToSaleAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class ThreeQuoteResource extends Resource
{
    public static $model = ThreeQuote::class;

    public static function label(): string
    {
        return 'Cotizaciones 3D';
    }

    public static function singularLabel(): string
    {
        return 'Cotización 3D';
    }

    public static function description(): ?string
    {
        return 'Cotizaciones enviadas por clientes desde Experiencia 3D.';
    }

    public function modelQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->newQuery()
            ->whereIn('status', ['sent', 'sold'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function with(): array
    {
        return ['scene', 'user', 'producto'];
    }

    public function fields(): array
    {
        return [
            Input::make('id')->title('ID')->readonly(),
            Input::make('status')->title('Estado')->readonly(),
            Input::make('scene.name')->title('Escena')->readonly(),
            Input::make('user.name')->title('Cliente')->readonly(),
            Input::make('producto.Nombre')->title('Producto')->readonly(),
            Input::make('boxes_required')->title('Cajas requeridas')->readonly(),
            Input::make('area_m2')->title('Área (m²)')->readonly(),
            Input::make('total')->title('Total')->readonly(),
            Input::make('sent_at')->title('Enviada')->readonly(),
            Input::make('sold_at')->title('Vendida')->readonly(),
            Input::make('venta_id')->title('Venta ID')->readonly(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('Detalles', '')
                ->render(function (ThreeQuote $quote) {
                    $id = $quote->id;
                    $estado = $quote->status;
                    $cliente = e($quote->user->name ?? 'Cliente Desconocido');
                    $producto = e($quote->producto->Nombre ?? 'Sin Producto');
                    $cajas = $quote->boxes_required;
                    $total = number_format((float)$quote->total, 2);
                    $fecha = $quote->created_at ? $quote->created_at->toFormattedDateString() : '-';
                    
                    // Badges para estado
                    $badgeBg = '#f1f5f9';
                    $badgeColor = '#475569';
                    $icon = 'bs.file-earmark-text';
                    $iconColor = '#64748b';
                    
                    if ($estado === 'draft') {
                        $badgeBg = '#fef3c7'; $badgeColor = '#92400e'; $iconColor = '#f59e0b';
                    } elseif ($estado === 'sent') {
                        $badgeBg = '#dbeafe'; $badgeColor = '#1e40af'; $iconColor = '#3b82f6'; $icon = 'bs.send';
                    } elseif ($estado === 'sold') {
                        $badgeBg = '#d1fae5'; $badgeColor = '#065f46'; $iconColor = '#10b981'; $icon = 'bs.check-circle';
                    }

                    // Botón Convertir
                    $btnConvert = '';
                    if ($estado === 'sent') {
                        $btnConvert = Button::make('Vender')
                            ->icon('bs.cart-check')
                            ->class('btn btn-sm btn-success style-convert-btn')
                            ->method('action', [
                                '_action' => ConvertThreeQuoteToSaleAction::name(),
                                '_models' => [(int) $quote->id],
                            ])->render();
                    }

                    // Enlace PDF
                    $btnPdf = '';
                    if ($quote->pdf_path) {
                        $url = asset('storage/'.$quote->pdf_path);
                        $btnPdf = "<a href='{$url}' target='_blank' class='btn btn-sm btn-outline-secondary' style='display:inline-flex;align-items:center;gap:4px;'><i class='bs.file-pdf'></i> PDF</a>";
                    }
                    
                    return "
                    <div style='display:flex; justify-content:space-between; align-items:center; width:100%;'>
                        <div style='display:flex; gap:16px; align-items:center;'>
                            <div style='width:48px; height:48px; border-radius:12px; background:#f8fafc; color:{$iconColor}; display:flex; align-items:center; justify-content:center; font-size:20px;'>
                                <i class='{$icon}'></i>
                            </div>
                            <div style='display:flex; flex-direction:column; gap:4px;'>
                                <div style='display:flex; align-items:center; gap:8px;'>
                                    <strong style='font-size:16px; color:#0f172a;'>Cotización #{$id} - {$cliente}</strong>
                                    <span style='background:{$badgeBg}; color:{$badgeColor}; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:600; text-transform:uppercase;'>{$estado}</span>
                                </div>
                                <div style='font-size:13px; color:#64748b; display:flex; gap:12px;'>
                                    <span><i class='bs.calendar' style='margin-right:4px;'></i>{$fecha}</span>
                                    <span><i class='bs.box' style='margin-right:4px;'></i>{$producto} ({$cajas} cajas)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style='display:flex; align-items:center; gap:20px;'>
                            <div style='display:flex; flex-direction:column; align-items:flex-end; gap:2px;'>
                                <div style='font-size:12px; color:#94a3b8;'>Total Estimado</div>
                                <strong style='font-size:20px; color:#10b981; font-weight:800;'>Bs {$total}</strong>
                            </div>
                            <div style='display:flex; gap:8px;'>
                                {$btnPdf}
                                {$btnConvert}
                            </div>
                        </div>
                    </div>
                    ";
                }),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('status', 'ESTADO'),
            Sight::make('scene.name', 'ESCENA'),
            Sight::make('user.name', 'CLIENTE'),
            Sight::make('producto.Nombre', 'PRODUCTO'),
            Sight::make('boxes_required', 'CAJAS REQUERIDAS'),
            Sight::make('area_m2', 'ÁREA (m²)'),
            Sight::make('total', 'TOTAL'),
            Sight::make('pdf_path', 'PDF PATH'),
            Sight::make('sent_at', 'ENVIADA'),
            Sight::make('sold_at', 'VENDIDA'),
            Sight::make('venta_id', 'VENTA ID'),
            Sight::make('created_at', 'CREADA'),
            Sight::make('updated_at', 'ACTUALIZADA'),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [
            new ConvertThreeQuoteToSaleAction(),
        ];
    }
}
