<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Backup;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;

class BackupListLayout extends Table
{
    protected $target = 'backups';

    protected function columns(): array
    {
        return [
            TD::make('file', 'Archivo')->render(fn($row) => basename($row['file'])),
            TD::make('size', 'Tamaño')->render(fn($row) => number_format($row['size']/1048576,2).' MB'),
            TD::make('date', 'Fecha')->render(fn($row) => date('Y-m-d H:i:s', $row['date'])),
            TD::make('actions', 'Acciones')
                ->align(TD::ALIGN_CENTER)
                ->render(function ($row) {
                    $url = route('platform.backup.download', ['file' => $row['file']]);
                    return Link::make('Descargar')
                        ->icon('bs.download')
                        ->href($url)
                        ->target('_blank');
                }),
        ];
    }
}
