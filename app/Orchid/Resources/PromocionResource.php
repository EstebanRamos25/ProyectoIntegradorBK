<?php

namespace App\Orchid\Resources;

use App\Models\Promocion;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class PromocionResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Promocion::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('Nombre')
                ->title('Nombre')
                ->placeholder('Ingresa el nombre de la promoción'),

            Input::make('Descripcion')
                ->title('Descripcion')
                ->placeholder('Ingresa la descripcion de la promoción'),

            Input::make('Descuento')
                ->type('number')
                ->step(0.01)
                ->title('Descuento')
                ->placeholder('Ingresa el porcentaje de descuento')
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
            TD::make('Nombre', 'NOMBRE'),
            TD::make('Descripcion', 'DESCRIPCION'),
            TD::make('Descuento', 'DESCUENTO'),

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
            Sight::make('Nombre', 'NOMBRE'),
            Sight::make('Descripcion', 'DESCRIPCION'),
            Sight::make('Descuento', 'DESCUENTO'),
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
}
