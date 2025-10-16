<?php

namespace App\Orchid\Resources;

use App\Models\Venta;
use App\Models\User;
use App\Models\Promocion;
use App\Models\Inventario;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class VentaResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Venta::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('Total')
                ->type('number')
                ->step(0.01)
                ->title('Total')
                ->placeholder('Ingresa el total de la venta'),

            DateTimer::make('Fecha')
                ->title('Fecha')
                ->placeholder('Selecciona la fecha de la venta'),

            // Selector para usuario relacionado
            Select::make('usuario_id')
                ->title('Usuario')
                ->fromModel(User::class, 'name')
                ->empty('Selecciona un usuario'),

            // Selector para promoción relacionada
            Select::make('promocion_id')
                ->title('Promoción')
                ->fromModel(Promocion::class, 'Nombre')
                ->empty('Selecciona una promoción'),

            // Selector para inventario relacionado
            Select::make('inventario_id')
                ->title('Inventario')
                ->fromModel(Inventario::class, 'id') // Puedes cambiar 'id' por otro campo más descriptivo si es necesario
                ->empty('Selecciona un inventario')
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
            TD::make('Total', 'TOTAL'),
            TD::make('Fecha', 'FECHA'),
            TD::make('usuario.name', 'USUARIO'), // Muestra el nombre del usuario relacionado
            TD::make('promocion.Nombre', 'PROMOCIÓN'), // Muestra el nombre de la promoción relacionada
            TD::make('inventario.id', 'INVENTARIO'), // Muestra el ID del inventario relacionado

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
            Sight::make('Total', 'TOTAL'),
            Sight::make('Fecha', 'FECHA'),
            Sight::make('usuario.name', 'USUARIO'),
            Sight::make('promocion.Nombre', 'PROMOCIÓN'),
            Sight::make('inventario.id', 'INVENTARIO'),
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
