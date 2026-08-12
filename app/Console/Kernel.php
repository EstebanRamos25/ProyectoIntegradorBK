<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Backup de base de datos — cada día a las 02:00
        $schedule->command('backup:run', ['--only-db', '--disable-notifications'])
            ->dailyAt('02:00')
            ->appendOutputTo(storage_path('logs/backup.log'));

        // Reentrenamiento del recomendador de productos 3D — cada lunes a medianoche.
        // Toma las ventas y cotizaciones 3D reales para actualizar el modelo MLP.
        // Se puede ejecutar manualmente con: php artisan three:reco-train
        $schedule->command('three:reco-train', ['--include-sent'])
            ->weeklyOn(1, '00:00') // 1 = lunes
            ->appendOutputTo(storage_path('logs/three-reco-train.log'))
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
