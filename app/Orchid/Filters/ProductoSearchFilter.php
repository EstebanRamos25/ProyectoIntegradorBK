<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class ProductoSearchFilter extends Filter
{
    public function name(): string
    {
        return 'Búsqueda';
    }

    public function parameters(): array
    {
        return ['search'];
    }

    public function run(Builder $builder): Builder
    {
        $raw = $this->request->get('search');
        $search = is_string($raw) ? trim($raw) : '';

        if ($search == '') {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($search) {
            if (ctype_digit($search)) {
                $query->orWhereKey((int) $search);
            }

            $like = '%' . $search . '%';

            $query
                ->orWhere('Nombre', 'like', $like)
                ->orWhere('Descripcion', 'like', $like)
                ->orWhere('Marca', 'like', $like)
                ->orWhere('Modelo', 'like', $like)
                ->orWhereHas('categoria', function (Builder $categoriaQuery) use ($like) {
                    $categoriaQuery->where('Nombre', 'like', $like);
                });
        });
    }

    public function display(): array
    {
        return [
            Input::make('search')
                ->title('Buscar producto')
                ->placeholder('Nombre, marca, modelo, categoría o ID')
                ->value($this->request->get('search')),
        ];
    }

    public function value(): string
    {
        $raw = $this->request->get('search');
        $search = is_string($raw) ? trim($raw) : '';

        return $this->name() . ': ' . $search;
    }
}
