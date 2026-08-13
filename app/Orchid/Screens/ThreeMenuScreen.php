<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class ThreeMenuScreen extends Screen
{
    public function query(): iterable
    {
        return [];
    }

    public function name(): ?string
    {
        return 'Experiencia 3D';
    }

    public function description(): ?string
    {
        return 'Gestiona tus escenarios 3D guardados.';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('three.admin-iframe')
        ];
    }
}
