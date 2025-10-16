<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Audit;

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
        return [];
    }

    public function layout(): array
    {
        return [
            ActivityListLayout::class,
        ];
    }
}
