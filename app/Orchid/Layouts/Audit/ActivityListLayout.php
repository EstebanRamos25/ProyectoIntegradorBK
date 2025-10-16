<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Audit;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ActivityListLayout extends Table
{
    protected $target = 'activities';

    protected function columns(): array
    {
        return [
            TD::make('id', 'ID')->width('80')->render(fn($a) => (string)$a->id),
            TD::make('log_name', 'Log')->render(fn($a) => e((string)$a->log_name)),
            TD::make('event', 'Evento')->render(fn($a) => e((string)$a->event)),
            TD::make('description', 'Descripción')->render(fn($a) => e((string)$a->description)),
            TD::make('subject_type', 'Modelo')->render(fn($a) => e(class_basename($a->subject_type))),
            TD::make('subject_id', 'ID Modelo')->render(fn($a) => (string)$a->subject_id),
            TD::make('causer', 'Usuario')->render(function($a){
                return optional($a->causer)->name ?: '-';
            }),
            TD::make('created_at', 'Fecha')->render(fn($a) => optional($a->created_at)->toDateTimeString()),
            TD::make('changes', 'Cambios')->render(function($a){
                $props = $a->properties ? $a->properties->toArray() : [];
                $changes = $props['attributes'] ?? [];
                $old = $props['old'] ?? [];
                $pairs = [];
                foreach ($changes as $k => $v) {
                    $ov = $old[$k] ?? null;
                    if ($ov === $v) continue;
                    $pairs[] = e($k).': '.e((string)$ov).' → '.e((string)$v);
                }
                return empty($pairs) ? '-' : '<div style="white-space:pre-wrap">'.implode("\n", $pairs).'</div>';
            })->width('30%'),
        ];
    }
}
