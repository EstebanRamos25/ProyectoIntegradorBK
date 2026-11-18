<?php

namespace App\Orchid\Resources;

use App\Models\ThreeQuote;
use App\Orchid\Actions\ConvertThreeQuoteToSaleAction;
use App\Orchid\Filters\ThreeQuoteStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Actions\Button;
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
        return 'Cotizaciones enviadas por clientes desde Experiencia 3D (ordenadas de más reciente a más antigua).';
    }

    public function paginationQuery(ResourceRequest $request, Model $model): Builder
    {
        $vista = (string) $request->query('vista', 'sent');
        $status = $vista === 'sold' ? 'sold' : 'sent';

        return $model->newQuery()
            ->where('status', $status)
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->orderByDesc('id');
    }

    public function modelQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->newQuery()
            ->whereIn('status', ['sent', 'sold'])
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
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
        $sentCount = ThreeQuote::query()->where('status', 'sent')->count();
        $soldCount = ThreeQuote::query()->where('status', 'sold')->count();
        $allCount = $sentCount + $soldCount;

        $currentVista = (string) request()->query('vista', '');
        if ($currentVista === '') {
            $currentVista = $sentCount > 0 ? 'sent' : 'all';
        }

        $baseUrl = request()->url();
        $urlSent = $baseUrl . '?vista=sent';
        $urlSold = $baseUrl . '?vista=sold';
        $urlAll  = $baseUrl . '?vista=all';

        $styleActive = "display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;background:#0f172a;color:#ffffff;font-weight:700;font-size:12px;text-decoration:none;box-shadow:0 2px 6px rgba(15,23,42,0.18);";
        $styleInactive = "display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;background:#f1f5f9;color:#475569;font-weight:600;font-size:12px;text-decoration:none;border:1px solid #e2e8f0;";

        $sSent = $currentVista === 'sent' ? $styleActive : $styleInactive;
        $sSold = $currentVista === 'sold' ? $styleActive : $styleInactive;
        $sAll  = $currentVista === 'all'  ? $styleActive : $styleInactive;

        $headerTabs = "
        <div style='display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:4px 0;'>
            <div style='display:flex;flex-wrap:wrap;gap:8px;align-items:center;'>
                <a href='{$urlSent}' style='{$sSent}'>
                    <span>⏳ Cotizaciones Pendientes</span>
                    <span style='background:#3b82f6;color:#fff;padding:1px 7px;border-radius:10px;font-size:11px;'>{$sentCount}</span>
                </a>
                <a href='{$urlSold}' style='{$sSold}'>
                    <span>✅ Cotizaciones Ya Hechas (Vendidas)</span>
                    <span style='background:#10b981;color:#fff;padding:1px 7px;border-radius:10px;font-size:11px;'>{$soldCount}</span>
                </a>
                <a href='{$urlAll}' style='{$sAll}'>
                    <span>📋 Todas</span>
                    <span style='background:#64748b;color:#fff;padding:1px 7px;border-radius:10px;font-size:11px;'>{$allCount}</span>
                </a>
            </div>
            <span style='font-size:11px;color:#64748b;font-weight:500;'>Mostrando las más recientes primero</span>
        </div>
        ";

        return [
            TD::make('Detalles', $headerTabs)
                ->render(function (ThreeQuote $quote) {
                    $id = $quote->id;
                    $estado = $quote->status;
                    $cliente = e($quote->user->name ?? 'Cliente Desconocido');
                    $escenaNombre = e($quote->scene->name ?? data_get($quote->quotation, 'scene_name', 'Escena 3D'));
                    $producto = e($quote->producto->Nombre ?? data_get($quote->quotation, 'material.name', 'Sin Producto de Piso'));
                    $wallMatName = data_get($quote->quotation, 'walls.material.name');
                    $wallBoxes = data_get($quote->quotation, 'walls.boxes_required');
                    $cajas = $quote->boxes_required;
                    $total = number_format((float)$quote->total, 2);

                    $fechaRef = $quote->sent_at ?? $quote->created_at;
                    $fecha = $fechaRef ? $fechaRef->format('d/m/Y H:i') : '-';

                    // Badges para estado
                    $badgeBg = '#f1f5f9';
                    $badgeColor = '#475569';
                    $estadoLabel = strtoupper((string) $estado);
                    $icon = 'bs.file-earmark-text';
                    $iconColor = '#64748b';

                    if ($estado === 'draft') {
                        $badgeBg = '#fef3c7'; $badgeColor = '#92400e'; $iconColor = '#f59e0b';
                        $estadoLabel = 'BORRADOR';
                    } elseif ($estado === 'sent') {
                        $badgeBg = '#dbeafe'; $badgeColor = '#1e40af'; $iconColor = '#3b82f6'; $icon = 'bs.send';
                        $estadoLabel = 'PENDIENTE';
                    } elseif ($estado === 'sold') {
                        $badgeBg = '#d1fae5'; $badgeColor = '#065f46'; $iconColor = '#10b981'; $icon = 'bs.check-circle';
                        $estadoLabel = 'VENDIDA';
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

                    // Botón Abrir Escena 3D
                    $btnScene = '';
                    if ($quote->three_scene_id) {
                        $sceneUrl = route('three.editor', ['sceneId' => $quote->three_scene_id]);
                        $btnScene = "<a href='{$sceneUrl}' target='_blank' class='btn btn-sm btn-outline-primary' style='display:inline-flex;align-items:center;gap:4px;' title='Abrir escenario 3D'><i class='bs.box'></i> Escena 3D</a>";
                    }

                    // Enlace PDF
                    $btnPdf = '';
                    if ($quote->pdf_path) {
                        $url = asset('storage/'.$quote->pdf_path);
                        $btnPdf = "<a href='{$url}' target='_blank' class='btn btn-sm btn-outline-secondary' style='display:inline-flex;align-items:center;gap:4px;'><i class='bs.file-pdf'></i> PDF</a>";
                    }

                    $wallInfoHtml = '';
                    if (!empty($wallMatName)) {
                        $wallMatSafe = e($wallMatName);
                        $wallBoxesTxt = $wallBoxes ? " ({$wallBoxes} cajas)" : '';
                        $wallInfoHtml = "<span>• Paredes: <strong>{$wallMatSafe}</strong>{$wallBoxesTxt}</span>";
                    }

                    return "
                    <div style='display:flex; justify-content:space-between; align-items:center; width:100%;'>
                        <div style='display:flex; gap:16px; align-items:center;'>
                            <div style='width:48px; height:48px; border-radius:12px; background:#f8fafc; color:{$iconColor}; display:flex; align-items:center; justify-content:center; font-size:20px;'>
                                <i class='{$icon}'></i>
                            </div>
                            <div style='display:flex; flex-direction:column; gap:4px;'>
                                <div style='display:flex; align-items:center; gap:8px; flex-wrap:wrap;'>
                                    <strong style='font-size:16px; color:#0f172a;'>Cotización #{$id} - {$cliente}</strong>
                                    <span style='background:#f1f5f9; color:#334155; font-size:11px; padding:2px 8px; border-radius:8px; font-weight:600;'>📁 {$escenaNombre}</span>
                                    <span style='background:{$badgeBg}; color:{$badgeColor}; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:700; text-transform:uppercase;'>{$estadoLabel}</span>
                                </div>
                                <div style='font-size:13px; color:#64748b; display:flex; flex-wrap:wrap; gap:12px;'>
                                    <span>📅 {$fecha}</span>
                                    <span>🪵 Piso: <strong>{$producto}</strong> ({$cajas} cajas)</span>
                                    {$wallInfoHtml}
                                </div>
                            </div>
                        </div>
                        
                        <div style='display:flex; align-items:center; gap:20px;'>
                            <div style='display:flex; flex-direction:column; align-items:flex-end; gap:2px;'>
                                <div style='font-size:12px; color:#94a3b8;'>Total Estimado</div>
                                <strong style='font-size:20px; color:#10b981; font-weight:800;'>Bs {$total}</strong>
                            </div>
                            <div style='display:flex; gap:8px; align-items:center;'>
                                {$btnScene}
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
