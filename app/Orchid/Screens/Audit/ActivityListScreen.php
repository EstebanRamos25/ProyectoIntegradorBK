<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Audit;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Spatie\Activitylog\Models\Activity;
use App\Orchid\Layouts\Audit\ActivityListLayout;

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
            Link::make('Exportar PDF')
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

    public function export()
    {
        $activities = Activity::query()
            ->with('causer')
            ->latest()
            ->get();

        $filename = 'auditoria-'.now()->format('Ymd_His').'.pdf';

        $pdf = Pdf::loadView('orchid.audit.report', [
            'generatedAt' => now(),
            'summary' => $this->buildSummary($activities),
            'activities' => $this->buildActivityRows($activities),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    protected function buildSummary(Collection $activities): array
    {
        $users = $activities
            ->countBy(fn (Activity $activity) => (string) (optional($activity->causer)->name ?: 'Sistema'))
            ->sortDesc();

        $models = $activities
            ->countBy(fn (Activity $activity) => class_basename((string) $activity->subject_type) ?: 'N/D')
            ->sortDesc();

        return [
            'total' => $activities->count(),
            'uniqueUsers' => $users->count(),
            'uniqueModels' => $models->count(),
            'byEvent' => $activities
                ->countBy(fn (Activity $activity) => $this->eventLabel($activity->event))
                ->sortDesc()
                ->all(),
            'topUsers' => $users
                ->take(5)
                ->all(),
            'topModels' => $models
                ->take(5)
                ->all(),
            'range' => [
                'from' => $activities->last()?->created_at,
                'to' => $activities->first()?->created_at,
            ],
        ];
    }

    protected function buildActivityRows(Collection $activities): array
    {
        return $activities
            ->map(function (Activity $activity): array {
                return [
                    'id' => $activity->id,
                    'log_name' => (string) $activity->log_name,
                    'event_key' => (string) $activity->event,
                    'event_label' => $this->eventLabel($activity->event),
                    'description' => (string) $activity->description,
                    'model' => class_basename((string) $activity->subject_type) ?: 'N/D',
                    'subject_id' => $activity->subject_id ? (string) $activity->subject_id : '—',
                    'user' => optional($activity->causer)->name ?: 'Sistema',
                    'created_at' => optional($activity->created_at)?->format('d/m/Y H:i:s') ?: '—',
                    'changes' => $this->formatChanges($activity),
                ];
            })
            ->all();
    }

    protected function formatChanges(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $newValues = $properties['attributes'] ?? [];
        $oldValues = $properties['old'] ?? [];
        $changes = [];

        foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $field) {
            $before = $oldValues[$field] ?? null;
            $after = $newValues[$field] ?? null;

            if ($before === $after) {
                continue;
            }

            $changes[] = [
                'field' => (string) $field,
                'before' => $this->stringifyValue($before),
                'after' => $this->stringifyValue($after),
            ];
        }

        return $changes;
    }

    protected function stringifyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
    }

    protected function eventLabel(?string $event): string
    {
        return match ($event) {
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            default => ucfirst((string) ($event ?: 'Evento')),
        };
    }
}
