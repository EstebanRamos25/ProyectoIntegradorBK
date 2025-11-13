<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Audit;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Spatie\Activitylog\Models\Activity;
use App\Orchid\Layouts\Audit\ActivityListLayout;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityListScreen extends Screen
{
    public function permission(): ?iterable
    {
        return ['platform.audit'];
    }

    public function query(): array
    {
        $activities = Activity::query()
            ->with('causer')
            ->latest()
            ->paginate(20);

        return [
            'activities' => $activities,
        ];
    }

    public function name(): ?string
    {
        return 'Auditoría';
    }

    public function description(): ?string
    {
        return 'Registro de acciones (creación, actualización, eliminación)';
    }

    public function commandBar(): array
    {
        return [
            Link::make('Exportar CSV')
                ->icon('bs.download')
                ->href(route('platform.audit.export'))
                ->target('_blank'),
        ];
    }

    public function layout(): array
    {
        return [
            ActivityListLayout::class,
        ];
    }

    public function export(): StreamedResponse
    {
        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID','Log','Evento','Descripción','Modelo','ID Modelo','Usuario','Fecha']);
            Activity::query()->with('causer')->orderByDesc('id')->chunk(500, function($chunk) use ($handle) {
                foreach ($chunk as $a) {
                    fputcsv($handle, [
                        $a->id,
                        $a->log_name,
                        $a->event,
                        $a->description,
                        class_basename((string)$a->subject_type),
                        $a->subject_id,
                        optional($a->causer)->name,
                        optional($a->created_at)->toDateTimeString(),
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->streamDownload($callback, 'auditoria.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
