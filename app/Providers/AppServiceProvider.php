<?php

namespace App\Providers;
use Orchid\Support\Facades\Dashboard;
use Orchid\Screen\Actions\Menu;
use Orchid\Platform\Models\Permission;

use Illuminate\Support\ServiceProvider;
use Orchid\Platform\ItemPermission;

use App\Orchid\Screens\Crud\ResourceListScreen;
use App\Orchid\Screens\Inventario\InventarioReportScreen;
use Illuminate\Support\Facades\Route;
use Orchid\Crud\ResourceRequest;
use Tabuna\Breadcrumbs\Trail;

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

        $this->app->booted(function () {
            if ($this->app->routesAreCached()) {
                return;
            }

            Route::domain((string) config('platform.domain'))
                ->prefix(Dashboard::prefix('/'))
                ->as('platform.')
                ->middleware(config('platform.middleware.private'))
                ->group(function () {
                    Route::get('inventario/export', [InventarioReportScreen::class, 'export'])
                        ->name('inventario.export');

                    Route::screen('/crud/list/{resource?}', ResourceListScreen::class)
                        ->name('resource.list')
                        ->breadcrumbs(function (Trail $trail) {
                            $resource = app(ResourceRequest::class)->resource();

                            return $trail->parent('platform.index')
                                ->push($resource::listBreadcrumbsMessage(), route('platform.resource.list', [$resource::uriKey()]));
                        });
                });
        });

    }
}
