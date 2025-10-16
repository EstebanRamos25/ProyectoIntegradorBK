<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Backup;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class BackupListLayout extends Table
{
    protected $target = 'backups';

    protected function columns(): array
    {
        return [
            TD::make('file', 'Archivo'),
            TD::make('size', 'Tamaño')->render(fn($row) => number_format($row['size']/1048576,2).' MB'),
            TD::make('date', 'Fecha')->render(fn($row) => date('Y-m-d H:i:s', $row['date'])),
        ];
    }
}
