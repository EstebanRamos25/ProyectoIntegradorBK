<?php

namespace App\Orchid\Resources;

use App\Models\Proyecto;
use App\Models\User; // Asegúrate de importar el modelo correcto
use App\Models\Producto;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class ProyectoResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Proyecto::class;

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
                ->placeholder('Ingresa el nombre del proyecto'),

            Input::make('Descripcion')
                ->title('Descripcion')
                ->placeholder('Ingresa la descripcion del proyecto'),

            Input::make('Tipo_Proyecto')
                ->title('Tipo de Proyecto')
                ->placeholder('Ingresa el tipo de proyecto'),

            // Selector para usuario relacionado
            Select::make('user_id')
                ->title('Usuario')
                ->fromModel(User::class, 'name')
                ->empty('Selecciona un usuario'),

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
            TD::make('Nombre', 'NOMBRE'),
            TD::make('Descripcion', 'DESCRIPCION'),
            TD::make('Tipo_Proyecto', 'TIPO DE PROYECTO'),
            TD::make('user.name', 'USUARIO'), // Muestra el nombre del usuario relacionado
            TD::make('producto.Nombre', 'PRODUCTO'),

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
            Sight::make('Tipo_Proyecto', 'TIPO DE PROYECTO'),
            Sight::make('user.name', 'USUARIO'),
            Sight::make('producto.Nombre', 'PRODUCTO'),
            Sight::make('created_at', 'Date of creation'),
            Sight::make('updated_at', 'Update date'),
        ];
    }

    /**
     * Eager load relations for index to avoid N+1 and enable related columns.
     */
    public function with(): array
    {
        return ['user', 'producto'];
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
