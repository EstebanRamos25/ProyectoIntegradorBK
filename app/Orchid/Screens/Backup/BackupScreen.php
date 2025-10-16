<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Backup;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use App\Orchid\Layouts\Backup\BackupListLayout;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Helpers\Format;
use Illuminate\Support\Facades\Storage;

class BackupScreen extends Screen
{
    public function query(): array
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupPath = trim(config('backup.backup.name', 'laravel'));
        $files = collect(Storage::disk($disk)->files("Laravel"))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->sortDesc()
            ->take(20)
            ->values();

        return [
            'backups' => $files->map(function($f){
                return [
                    'file' => $f,
                    'size' => Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local')->size($f),
                    'date' => Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local')->lastModified($f),
                ];
            }),
        ];
    }

    public function name(): ?string
    {
        return 'Backups';
    }

    public function description(): ?string
    {
        return 'Crea y administra copias de seguridad';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Generar backup ahora')
                ->method('runNow')
                ->icon('bs.download'),
        ];
    }

    public function layout(): array
    {
        return [
            BackupListLayout::class,
        ];
    }

    public function runNow()
    {
    Artisan::call('backup:run');
        
        
        return back();
    }
}
