<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;

class ThreeQuoteStatusFilter extends Filter
{
    public function name(): string
    {
        return 'Estado y Búsqueda';
    }

    public function parameters(): array
    {
        return ['vista', 'q'];
    }

    public function run(Builder $builder): Builder
    {
        $vista = (string) $this->request->get('vista', '');
        if ($vista === 'sent') {
            $builder->where('status', 'sent');
        } elseif ($vista === 'sold') {
            $builder->where('status', 'sold');
        }

        $q = trim((string) $this->request->get('q', ''));
        if ($q !== '') {
            $builder->where(function (Builder $query) use ($q) {
                if (ctype_digit($q)) {
                    $query->orWhereKey((int) $q);
                }
                $like = '%' . $q . '%';
                $query->orWhereHas('user', function (Builder $uq) use ($like) {
                    $uq->where('name', 'like', $like)
                       ->orWhere('email', 'like', $like);
                })->orWhereHas('producto', function (Builder $pq) use ($like) {
                    $pq->where('Nombre', 'like', $like);
                })->orWhereHas('scene', function (Builder $sq) use ($like) {
                    $sq->where('name', 'like', $like);
                });
            });
        }

        return $builder;
    }

    public function display(): array
    {
        return [
            Select::make('vista')
                ->title('Estado de Cotización')
                ->options([
                    'sent' => '⏳ Pendientes por atender (Enviadas)',
                    'sold' => '✅ Cotizaciones ya hechas (Vendidas)',
                    'all'  => '📋 Todas las cotizaciones',
                ])
                ->value($this->request->get('vista', 'sent')),

            Input::make('q')
                ->title('Buscar cliente, proyecto o producto')
                ->placeholder('Ej. Juan, cuarto de invitados, cerámica...')
                ->value($this->request->get('q')),
        ];
    }

    public function value(): string
    {
        $vista = (string) $this->request->get('vista', '');
        $q = trim((string) $this->request->get('q', ''));

        $labels = [
            'sent' => 'Pendientes',
            'sold' => 'Vendidas',
            'all'  => 'Todas',
        ];

        $parts = [];
        if (isset($labels[$vista])) {
            $parts[] = 'Vista: ' . $labels[$vista];
        }
        if ($q !== '') {
            $parts[] = 'Búsqueda: ' . $q;
        }

        return implode(' | ', $parts) ?: $this->name();
    }
}

