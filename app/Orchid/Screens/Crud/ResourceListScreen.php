<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Crud;

use App\Orchid\Resources\InventarioResource;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Link;

class ResourceListScreen extends \Orchid\Crud\Screens\ListScreen
{
    /**
     * Button commands.
     *
     * @return Action[]
     */
    public function commandBar(): array
    {
        $commandBar = parent::commandBar();

        if ($this->resource::uriKey() !== InventarioResource::uriKey()) {
            return $commandBar;
        }

        $export = Link::make('Exportar reporte PDF')
            ->icon('bs.download')
            ->href(route('platform.inventario.export'))
            ->target('_blank');

        array_splice($commandBar, 1, 0, [$export]);

        return $commandBar;
    }
}
