<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Three;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class ThreeDemoScreen extends Screen
{
    public string $name = 'Experiencia 3D';

    public string $description = 'Experiencia 3D dentro del panel de administración.';

    public function query(): iterable
    {
        return [
            'src' => route('three.menu'),
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.three.experience'),
        ];
    }
}
