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
            TD::make('id'),
            TD::make('Codigo_Lote', 'LOTE'),
            TD::make('Tono', 'TONO'),
            TD::make('Calibre', 'CALIBRE'),
            TD::make('Cajas_Disponibles', 'CAJAS DISP.'),
            TD::make('Cajas_Entrada', 'CAJAS ENTR.'),
            TD::make('Cantidad', 'CANTIDAD (LEGACY)'),
            TD::make('Ubicacion', 'UBICACIÓN'),
            TD::make('Estado', 'ESTADO'),
            TD::make('producto.Nombre', 'PRODUCTO'), // Muestra el nombre del producto relacionado

            TD::make('created_at', 'Date of creation')
                ->render(function ($model) {
                    return $model->created_at->toDateTimeString();
                }),

            TD::make('updated_at', 'Update date')
                ->render(function ($model) {
                    return $model->updated_at->toDateTimeString();
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
            Sight::make('Cajas_Entrada', 'CAJAS DE ENTRADA'),
            Sight::make('Cantidad', 'CANTIDAD (LEGACY)'),
            Sight::make('Ubicacion', 'UBICACIÓN'),
            Sight::make('Estado', 'ESTADO'),
            Sight::make('producto.Nombre', 'PRODUCTO'),
            Sight::make('created_at', 'Date of creation'),
            Sight::make('updated_at', 'Update date'),
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
}
