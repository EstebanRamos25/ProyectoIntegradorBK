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
                $new = $props['attributes'] ?? [];
                $old = $props['old'] ?? [];
                if (empty($new) && empty($old)) return '-';

                $rows = '';
                $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                foreach ($keys as $k) {
                    $ov = $old[$k] ?? null;
                    $nv = $new[$k] ?? null;
                    if ($ov === $nv) continue;
                    $ovStr = is_scalar($ov) || is_null($ov) ? (string)$ov : json_encode($ov, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
                    $nvStr = is_scalar($nv) || is_null($nv) ? (string)$nv : json_encode($nv, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
                    $rows .= '<tr><th style="vertical-align:top">'.e($k).'</th><td style="white-space:pre-wrap">'.e($ovStr).'</td><td style="white-space:pre-wrap">'.e($nvStr).'</td></tr>';
                }
                if ($rows === '') return '-';
                $table = '<table class="table table-sm"><thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead><tbody>'.$rows.'</tbody></table>';
                return '<details><summary>Ver cambios</summary>'.$table.'</details>';
            }),
        ];
    }
}
