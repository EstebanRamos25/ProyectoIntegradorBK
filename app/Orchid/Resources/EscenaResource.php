<?php

namespace App\Orchid\Resources;

use App\Models\ThreeScene;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class EscenaResource extends Resource
{
    public static $model = ThreeScene::class;

    public static function label(): string
    {
        return 'Historial de Escenas 3D';
    }

    public static function singularLabel(): string
    {
        return 'Escena 3D';
    }

    public static function description(): ?string
    {
        return 'Historial de todas las escenas creadas por clientes en el editor 3D.';
    }

    public function with(): array
    {
        return ['user'];
    }

    public function fields(): array
    {
        return [
            Input::make('name')->title('Nombre de la Escena')->readonly(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('id', 'ID'),
            TD::make('name', 'NOMBRE'),
            TD::make('user.name', 'CLIENTE'),
            TD::make('quotes_count', 'COTIZACIONES')->render(function (ThreeScene $scene) {
                return $scene->quotes()->count();
            }),
            TD::make('updated_at', 'ÚLTIMA MODIFICACIÓN')->render(function ($model) {
                return $model->updated_at->toDateTimeString();
            }),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('name', 'NOMBRE'),
            Sight::make('user.name', 'CLIENTE'),
            Sight::make('created_at', 'CREADA'),
            Sight::make('updated_at', 'ACTUALIZADA'),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}
