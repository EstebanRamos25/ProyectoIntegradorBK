<?php

namespace App\Orchid\Resources;

use App\Models\Escena;
use App\Models\Proyecto;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class EscenaResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Escena::class;

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
                ->placeholder('Ingresa el nombre de la escena'),

            Input::make('Descripcion')
                ->title('Descripcion')
                ->placeholder('Ingresa la descripcion de la escena'),

            Input::make('Tipo_Diseño')
                ->title('Tipo de Diseño')
                ->placeholder('Ingresa el tipo de diseño'),

            // Selector para proyecto relacionado
            Select::make('proyecto_id')
                ->title('Proyecto')
                ->fromModel(Proyecto::class, 'Nombre')
                ->empty('Selecciona un proyecto')
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
            TD::make('Tipo_Diseño', 'TIPO DE DISEÑO'),
            TD::make('proyecto.Nombre', 'PROYECTO'), // Muestra el nombre del proyecto relacionado

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
            Sight::make('Tipo_Diseño', 'TIPO DE DISEÑO'),
            Sight::make('proyecto.Nombre', 'PROYECTO'),
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
