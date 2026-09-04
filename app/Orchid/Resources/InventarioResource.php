<?php

namespace App\Orchid\Resources;

use App\Models\Inventario;
use App\Models\Producto;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class InventarioResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Inventario::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('Cantidad')
                ->type('number')
                ->title('Cantidad (legacy)')
                ->placeholder('Compatibilidad: si aún no manejas cajas, úsalo como “stock”')
                ->help('Recomendado: usar Cajas_Entrada/Cajas_Disponibles. Este campo existe solo por compatibilidad con datos antiguos.'),

            Input::make('Cajas_Entrada')
                ->type('number')
                ->title('Cajas de entrada')
                ->placeholder('Ej: 100')
                ->help('Cuántas cajas ingresaron a almacén para este lote.'),

            Input::make('Cajas_Disponibles')
                ->type('number')
                ->title('Cajas disponibles')
                ->placeholder('Ej: 75')
                ->help('Si lo dejas vacío al crear, el sistema lo inicializa igual a Cajas_Entrada.'),

            Input::make('Costo_M2')
                ->type('number')
                ->step(0.01)
                ->title('Costo de compra por m²')
                ->placeholder('Ej: 120.00')
                ->help('Costo real de compra de este lote. Se usa para valuación y ganancia.'),

            Input::make('Codigo_Lote')
                ->title('Código de lote')
                ->placeholder('Ej: 2024-A')
                ->help('Identificador de producción. Idealmente no mezclar lotes distintos en una misma instalación.'),

            Input::make('Tono')
                ->title('Tono')
                ->placeholder('Ej: T01')
                ->help('Variación de color/tono del lote (muy relevante en cerámica).'),

            Input::make('Calibre')
                ->title('Calibre')
                ->placeholder('Ej: C2')
                ->help('Variación dimensional/espesor del lote (para evitar diferencias al instalar).'),

            DateTimer::make('Fecha_Ingreso')
                ->title('Fecha de ingreso')
                ->allowInput()
                ->placeholder('Selecciona la fecha de ingreso'),

            Input::make('Ubicacion')
                ->title('Ubicación')
                ->placeholder('Ingresa la ubicación del inventario'),

            Input::make('Estado')
                ->title('Estado')
                ->placeholder('Ingresa el estado del inventario'),

            // Selector para producto relacionado
            Select::make('producto_id')
                ->title('Producto')
                ->fromModel(Producto::class, 'Nombre')
                ->empty('Selecciona un producto')
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
            TD::make('producto.Nombre', 'PRODUCTO Y DETALLES')
                ->render(function ($model) {
                    $nombre = e($model->producto->Nombre ?? 'Sin Producto');
                    $lote = e($model->Codigo_Lote ?? '-');
                    $tono = e($model->Tono ?? '-');
                    $calibre = e($model->Calibre ?? '-');
                    
                    return "<div style='display:flex;flex-direction:column;gap:4px;'>
                                <strong style='font-size:14px;color:#0f172a;'>{$nombre}</strong>
                                <div style='font-size:12px;color:#64748b;display:flex;gap:12px;'>
                                    <span><strong>Lote:</strong> {$lote}</span>
                                    <span><strong>Tono:</strong> {$tono}</span>
                                    <span><strong>Calibre:</strong> {$calibre}</span>
                                </div>
                            </div>";
                }),

            TD::make('Cajas_Disponibles', 'STOCK Y VALOR')
                ->render(function ($model) {
                    $disp = (int) ($model->Cajas_Disponibles ?? $model->Cantidad ?? 0);
                    $entr = (int) $model->Cajas_Entrada;
                    $costo = number_format((float)$model->Costo_M2, 2, '.', ',');
                    $color = $disp > 0 ? '#10b981' : '#ef4444';
                    
                    return "<div style='display:flex;flex-direction:column;gap:4px;'>
                                <div style='font-size:15px;font-weight:700;color:{$color};'>{$disp} cajas disp.</div>
                                <div style='font-size:12px;color:#64748b;'>Entrantes: {$entr} | Costo: Bs {$costo}/m²</div>
                            </div>";
                }),

            TD::make('Estado', 'UBICACIÓN Y ESTADO')
                ->render(function ($model) {
                    $ubi = e($model->Ubicacion ?? 'No asignada');
                    $estado = e($model->Estado ?? '-');
                    
                    $disp = (int) ($model->Cajas_Disponibles ?? $model->Cantidad ?? 0);
                    if ($disp <= 0) {
                        $estado = 'Agotado';
                    }
                    
                    $badgeBg = $estado === 'Disponible' ? '#d1fae5' : ($estado === 'Agotado' ? '#fef2f2' : '#fef3c7');
                    $badgeColor = $estado === 'Disponible' ? '#065f46' : ($estado === 'Agotado' ? '#ef4444' : '#92400e');
                    
                    return "<div style='display:flex;flex-direction:column;align-items:flex-start;gap:6px;'>
                                <div style='font-size:13px;color:#334155;'><i class='bs.geo-alt' style='margin-right:4px;'></i>{$ubi}</div>
                                <span style='background:{$badgeBg};color:{$badgeColor};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;'>{$estado}</span>
                            </div>";
                }),

            TD::make('created_at', 'FECHA DE CREACIÓN')
                ->render(function ($model) {
                    return "<span style='font-size:12px;color:#94a3b8;'>" . $model->created_at->toFormattedDateString() . "</span>";
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
            Sight::make('Codigo_Lote', 'LOTE'),
            Sight::make('Tono', 'TONO'),
            Sight::make('Calibre', 'CALIBRE'),
            Sight::make('Cajas_Disponibles', 'CAJAS DISPONIBLES'),
            Sight::make('Costo_M2', 'COSTO/M²'),
            Sight::make('Cajas_Entrada', 'CAJAS DE ENTRADA'),
            Sight::make('Cantidad', 'CANTIDAD (LEGACY)'),
            Sight::make('Ubicacion', 'UBICACIÓN'),
            Sight::make('Estado', 'ESTADO'),
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

    /**
     * Eager load relations for index.
     */
    public function with(): array
    {
        return ['producto'];
    }

    /**
     * Action to create and update the model
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function save(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $model->fill($request->all())->save();
        
        // Invalidar caché del catálogo 3D al agregar/editar stock
        \Illuminate\Support\Facades\Cache::forget('three.materials.catalog');
    }

    /**
     * Action to delete the model
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \Exception
     */
    public function onDelete(\Illuminate\Database\Eloquent\Model $model): void
    {
        $model->delete();
        
        // Invalidar caché del catálogo 3D al eliminar stock
        \Illuminate\Support\Facades\Cache::forget('three.materials.catalog');
    }
}
