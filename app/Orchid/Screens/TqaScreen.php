<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TqaScreen extends Screen
{
    public $name        = 'TQA Automatización';
    public $description = 'Elige el tipo de prueba que deseas ejecutar';

    public function query(): array
    {
        return [];
    }

    public function commandBar(): array
    {
        return [
            Button::make('Smoke Tests')
                ->icon('rocket')
                ->method('runSmoke')
                ->typeColor('info'),

            Button::make('Test Cases')
                ->icon('pencil')
                ->method('runTestCases')
                ->typeColor('warning'),

            Button::make('Pruebas de Integración')
                ->icon('layers')
                ->method('runIntegration')
                ->typeColor('success'),

            Button::make('Carga (Locust)')
                ->icon('activity')
                ->method('runLocust')
                ->typeColor('dark'),
        ];
    }

    public function layout(): array
{
    return [
        // Layout::rows([
        //     Legend::make([
        //         'title' => 'Pruebas de Carga Interactivas con Locust',
        //         'description' => 
        //             "1. Abre una terminal y navega a la carpeta testing-scripts de tu proyecto.<br>" .
        //             "2. Activa el entorno virtual de Python: <code>venv\\Scripts\\activate</code><br>" .
        //             "3. Ejecuta: <code>python prueba_locust_ui.py</code><br>" .
        //             "4. En tu navegador, abre: <a href=\"http://localhost:8089\" target=\"_blank\">http://localhost:8089</a><br>" .
        //             "5. En “Number of users to simulate”, ingresa 20 y ajusta “Spawn rate”.<br>" .
        //             "6. Haz clic en “Start swarming” y observa las métricas en vivo."
        //     ]),

        //     Link::make('Abrir UI de Locust')
        //         ->icon('bs.bar-chart')
        //         ->url('http://localhost:8089')
        //         ->target('_blank')
        //         ->title('UI de Locust')
        //         ->description('Haz clic aquí para abrir la interfaz de Locust (asegúrate de haber arrancado el script).'),
        // ]),
    ];
}


    /**
     * Al presionar “Smoke Tests”, redirige a la ruta que ejecuta smoke().
     */
    public function runSmoke()
    {
        Toast::info('Iniciando Pruebas de Humo...');
        return redirect()->route('testing.smoke');
    }

    /**
     * Al presionar “Test Cases”, redirige a la ruta que ejecuta testCases().
     */
    public function runTestCases()
    {
        Toast::info('Iniciando Test Cases...');
        return redirect()->route('testing.testcases');
    }

    /**
     * Al presionar “Pruebas de Integración”, redirige a la ruta que ejecuta integration().
     */
    public function runIntegration()
    {
        Toast::info('Iniciando Pruebas de Integración...');
        return redirect()->route('testing.integration');
    }

    /**
     * Al presionar “Carga (Locust)”, redirige a la ruta que ejecuta locust().
     */
    public function runLocust()
    {
        Toast::info('Iniciando Pruebas de Carga (Locust)...');
        return redirect()->route('testing.locust');
    }
}
