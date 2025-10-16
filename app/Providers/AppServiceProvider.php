<?php

namespace App\Providers;
use Orchid\Support\Facades\Dashboard;
use Orchid\Screen\Actions\Menu;
use Orchid\Platform\Models\Permission;

use Illuminate\Support\ServiceProvider;
use Orchid\Platform\ItemPermission;

// Importa las clases de Orchid que vas a necesitar


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1) Registra el permiso 'platform.testing'
        Dashboard::registerPermissions(
    ItemPermission::group('Testing')
        ->addPermission('platform.testing', 'Acceso a TQA Automatización')
);

          

    }
}
