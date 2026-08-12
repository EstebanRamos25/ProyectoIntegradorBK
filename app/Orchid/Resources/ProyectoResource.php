<?php

namespace App\Orchid\Resources;

use App\Models\User;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;

class ProyectoResource extends Resource
{
    public static $model = User::class;

    public static function label(): string
    {
        return 'Actividad por Usuario (3D)';
    }

    public static function singularLabel(): string
    {
        return 'Actividad de Usuario';
    }

    public static function description(): ?string
    {
        return 'Resumen de escenas y cotizaciones generadas por cada cliente.';
    }

    public function modelQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->newQuery()
            ->withCount(['threeScenes', 'threeQuotes'])
            ->having('three_scenes_count', '>', 0)
            ->orHaving('three_quotes_count', '>', 0);
    }

    public function fields(): array
    {
        return [
            Input::make('name')->title('Nombre')->readonly(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('id', 'ID'),
            TD::make('name', 'CLIENTE'),
            TD::make('email', 'CORREO'),
            TD::make('three_scenes_count', 'ESCENAS 3D GUARDADAS'),
            TD::make('three_quotes_count', 'COTIZACIONES GENERADAS'),
            TD::make('created_at', 'REGISTRADO')->render(function ($model) {
                return $model->created_at->toFormattedDateString();
            }),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('name', 'CLIENTE'),
            Sight::make('email', 'CORREO'),
            Sight::make('three_scenes_count', 'ESCENAS 3D'),
            Sight::make('three_quotes_count', 'COTIZACIONES'),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}
