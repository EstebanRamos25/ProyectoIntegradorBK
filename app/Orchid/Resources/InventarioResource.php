<?php

namespace App\Orchid\Resources;

use App\Models\Inventario;
use App\Models\Producto;
use Orchid\Crud\Resource;
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
                ->title('Cantidad')
                ->placeholder('Ingresa la cantidad'),

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
            TD::make('Cantidad', 'CANTIDAD'),
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
            Sight::make('Cantidad', 'CANTIDAD'),
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
