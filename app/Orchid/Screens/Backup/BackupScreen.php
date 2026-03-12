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
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Facades\Log;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\DateTimer;

class BackupScreen extends Screen
{
    public function query(): array
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupPath = trim(config('backup.backup.name', 'laravel-backup'));

        $from = request('from');
        $to = request('to');

        $files = collect(Storage::disk($disk)->files($backupPath))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(function ($f) use ($disk) {
                return [
                    'file' => $f,
                    'size' => Storage::disk($disk)->size($f),
                    'date' => Storage::disk($disk)->lastModified($f),
                ];
            })
            ->when($from, function ($c) use ($from) {
                $fromTs = strtotime($from . (strlen($from) <= 10 ? ' 00:00:00' : '')) ?: null;
                return $fromTs ? $c->filter(fn($r) => $r['date'] >= $fromTs) : $c;
            })
            ->when($to, function ($c) use ($to) {
                $toTs = strtotime($to . (strlen($to) <= 10 ? ' 23:59:59' : '')) ?: null;
                return $toTs ? $c->filter(fn($r) => $r['date'] <= $toTs) : $c;
            })
            ->sortByDesc('date')
            ->take(20)
            ->values();

        return [
            'backups' => $files,
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
            Button::make('Limpiar backups antiguos')
                ->method('cleanup')
                ->icon('bs.trash')
                ->confirm('Esto eliminará backups antiguos según la política de cleanup. ¿Desea continuar?'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::rows([
                DateTimer::make('from')
                    ->title('Desde')
                    ->allowInput()
                    ->format('Y-m-d')
                    ->value(request('from')),
                DateTimer::make('to')
                    ->title('Hasta')
                    ->allowInput()
                    ->format('Y-m-d')
                    ->value(request('to')),
                Button::make('Aplicar filtro')
                    ->icon('bs.filter')
                    ->method('applyFilter'),
            ])->title('Filtrar por fecha'),
            BackupListLayout::class,
        ];
    }

    public function runNow(): RedirectResponse
    {
        try {
            $exit = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);

            $output = Artisan::output();

            if ($exit !== 0) {
                Log::warning('Backup run failed from UI', ['exit' => $exit, 'output' => $output]);
                Toast::warning('No se pudo generar el backup. Revisa la configuración del volcado y los permisos del almacenamiento.');

                return back();
            }

            Toast::success('Backup de base de datos generado correctamente.');
        } catch (\Throwable $e) {
            Log::warning('Backup run exception from UI', ['exception' => $e->getMessage()]);
            Toast::warning('No se pudo generar el backup. Revisa la configuración del volcado y los permisos del almacenamiento.');

            return back();
        }

        // Registrar actividad de backup
        try {
            $disk = config('backup.backup.destination.disks')[0] ?? 'local';
            $dir = trim(config('backup.backup.name', 'laravel-backup'));
            $files = collect(Storage::disk($disk)->files($dir))
                ->filter(fn($f) => str_ends_with($f, '.zip'))
                ->sortDesc()
                ->take(1);
            $last = $files->first();
            if ($last) {
                activity('backups')
                ->causedBy(Auth::user())
                ->withProperties([
                    'file' => $last,
                    'disk' => $disk,
                ])
                ->log('Generó un backup de la base de datos');
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        return back();
    }

    public function cleanup(): RedirectResponse
    {
        try {
            $exit = Artisan::call('backup:clean');
            $output = Artisan::output();

            if ($exit !== 0) {
                Log::warning('Backup clean failed from UI', ['exit' => $exit, 'output' => $output]);
                Toast::warning('No se pudo limpiar los backups antiguos. Revisa permisos y configuración.');

                return back();
            }

            Toast::success('Backups antiguos limpiados correctamente.');
        } catch (\Throwable $e) {
            Log::warning('Backup clean exception from UI', ['exception' => $e->getMessage()]);
            Toast::warning('No se pudo limpiar los backups antiguos. Revisa permisos y configuración.');
        }

        return back();
    }

    public function applyFilter(): RedirectResponse
    {
        $params = [];
        if (request()->filled('from')) { $params['from'] = request('from'); }
        if (request()->filled('to')) { $params['to'] = request('to'); }
        return redirect()->route('platform.backup', $params);
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
