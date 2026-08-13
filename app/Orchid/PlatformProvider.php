<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            // ─── Catálogo ─────────────────────────────────────────────────
            Menu::make('Categorías')
                ->icon('bs.tags')
                ->route('platform.resource.list', 'categoria-resources')
                ->title('Catálogo'),

            Menu::make('Productos')
                ->icon('bs.grid')
                ->route('platform.resource.list', 'producto-resources'),

            // ─── Inventario ───────────────────────────────────────────────
            Menu::make('Inventario')
                ->icon('bs.boxes')
                ->route('platform.resource.list', 'inventario-resources')
                ->title('Inventario'),

            // ─── Ventas ───────────────────────────────────────────────────
            Menu::make('Cotizaciones 3D')
                ->icon('bs.file-earmark-text')
                ->route('platform.resource.list', 'three-quote-resources')
                ->title('Ventas'),

            Menu::make('Ventas')
                ->icon('bs.cart-check')
                ->route('platform.resource.list', 'venta-resources'),

            Menu::make('Reporte de ganancias')
                ->icon('bs.cash-stack')
                ->route('platform.ventas.report')
                ->permission('platform.ventas.report'),

            // ─── Experiencia 3D ───────────────────────────────────────────
            Menu::make('Editor 3D')
                ->icon('bs.box')
                ->route('platform.three.menu')
                ->title('Experiencia 3D'),

            Menu::make('Ir a Room demo')
                ->icon('bs.play-circle')
                ->route('platform.three.room'),

            Menu::make('Historial de Escenas')
                ->icon('bs.camera')
                ->route('platform.resource.list', 'escena-resources'),

            Menu::make('Actividad por Usuario')
                ->icon('bs.person-lines-fill')
                ->route('platform.resource.list', 'proyecto-resources'),

            // ─── Control de Acceso ────────────────────────────────────────
            Menu::make('Usuarios')
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title('Control de Acceso'),

            Menu::make('Roles')
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),

            // ─── Monitoreo ────────────────────────────────────────────────
            Menu::make('Auditoría')
                ->icon('bs.eye')
                ->route('platform.audit')
                ->permission('platform.audit')
                ->title('Monitoreo'),

            // ─── Mantenimiento ────────────────────────────────────────────
            Menu::make('Backups')
                ->icon('bs.hdd')
                ->route('platform.backup')
                ->permission('platform.backup')
                ->title('Mantenimiento'),

            Menu::make('TQA Automatización')
                ->icon('bs.check2-circle')
                ->route('platform.tqa')
                ->permission('platform.testing'),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group('Sistema')
                ->addPermission('platform.systems.roles', 'Gestión de Roles')
                ->addPermission('platform.systems.users', 'Gestión de Usuarios'),
            ItemPermission::group('Monitoreo')
                ->addPermission('platform.audit', 'Ver registros de auditoría'),
            ItemPermission::group('Ventas')
                ->addPermission('platform.ventas.report', 'Ver reporte de ganancias (3D)'),
            ItemPermission::group('Mantenimiento')
                ->addPermission('platform.backup', 'Gestionar backups')
                ->addPermission('platform.testing', 'TQA Automatización'),
        ];
    }
}
