<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Backup;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use App\Orchid\Layouts\Backup\BackupListLayout;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;

class BackupScreen extends Screen
{
    public function query(): array
    {
    $disk = config('backup.backup.destination.disks')[0] ?? 'local';
    $backupPath = trim(config('backup.backup.name', 'laravel-backup'));
    $files = collect(Storage::disk($disk)->files($backupPath))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->sortDesc()
            ->take(20)
            ->values();

        return [
        'backups' => $files->map(function($f) use ($disk) {
                return [
                    'file' => $f,
            'size' => Storage::disk($disk)->size($f),
            'date' => Storage::disk($disk)->lastModified($f),
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

    public function runNow(): RedirectResponse
    {
        // Ejecutar backup sólo de la base de datos y sin notificaciones
        Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true,
        ]);

        // Registrar actividad de backup
        try {
            $disk = config('backup.backup.destination.disks')[0] ?? 'local';
            $dir = trim(config('backup.backup.name', 'laravel-backup'));
            $files = collect(Storage::disk($disk)->files($dir))
                ->filter(fn($f) => str_ends_with($f, '.zip'))
                ->sortDesc()
                ->take(1);
            $last = $files->first();
            activity('backups')
                ->causedBy(Auth::user())
                ->withProperties([
                    'file' => $last,
                    'disk' => $disk,
                ])
                ->log('Generó un backup de la base de datos');
        } catch (\Throwable $e) {
            // silencioso
        }

        return back();
    }

    public function download(?string $file = null): BinaryFileResponse
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $file = $file ?? request('file');

        // Seguridad básica: el archivo debe estar dentro del prefijo de backup
        $baseDir = trim(config('backup.backup.name', 'laravel-backup'));
        if (!is_string($file) || !str_starts_with($file, $baseDir . '/')) {
            abort(404);
        }

        if (!Storage::disk($disk)->exists($file)) {
            abort(404);
        }

        $path = Storage::disk($disk)->path($file);
        return response()->download($path, basename($file));
    }
}
