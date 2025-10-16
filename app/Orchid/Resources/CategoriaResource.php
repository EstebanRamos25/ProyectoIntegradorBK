<?php

namespace App\Orchid\Resources;

use App\Models\Categoria;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class CategoriaResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Categoria::class;

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
            ->placeholder('Ingresa el nombre de la categoria'),
            Input::make('Descripcion')
            ->title('Descripcion')
            ->placeholder('Ingresa la descripcion de la categoria'),
            Input::make('Tipo_Material')
            ->title('Tipo_Material')
            ->placeholder('Ingresa el tipo del material'),
            Input::make('Resistencia')
            ->title('Resistencia')
            ->placeholder('Ingresa el tipo de resistencia')

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

            TD::make('Nombre','NOMBRE'),
            TD::make('Descripcion','DESCRIPCION'),
            TD::make('Tipo_Material','TIPO DE MATERIAL'),
            TD::make('Resistencia','RESISTENCIA'),

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
            Sight::make('id','ID'),
            Sight::make('Nombre','NOMBRE'),
            Sight::make('Descripcion','DESCRIPCION'),
            Sight::make('Tipo_Material','TIPO DE MATERIAL'),
            Sight::make('Resistencia','RESISTENCIA'),
            Sight::make('created_at','Date of creation'),
            Sight::make('updated_at','Update date'),


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
