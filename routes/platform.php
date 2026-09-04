<?php

declare(strict_types=1);

use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use App\Orchid\Screens\Audit\ActivityListScreen;
use App\Orchid\Screens\Backup\BackupScreen;
use App\Orchid\Screens\Three\ThreeDemoScreen;
use App\Orchid\Screens\Three\ThreeRoomScreen;
use App\Orchid\Screens\Ventas\VentasProfitReportScreen;
use App\Orchid\Screens\Inventario\InventarioReportScreen;
use App\Orchid\Screens\Inventario\LowStockScreen;
use App\Orchid\Screens\Recommender\MLDecisionReportScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use App\Orchid\Screens\TqaScreen;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

Route::screen('tqa', TqaScreen::class)
    ->name('platform.tqa')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('TQA Automatización'));

//Route::screen('idea', Idea::class, 'platform.screens.idea');

// Auditoría: exportación PDF (definir ANTES para evitar colisión con Screen)
Route::get('audit/export', [ActivityListScreen::class, 'export'])->name('platform.audit.export');

// Ventas: exportación PDF (definir ANTES para evitar colisión con Screen)
Route::get('ventas/report/export', [VentasProfitReportScreen::class, 'export'])->name('platform.ventas.report.export');

// Auditoría
Route::screen('audit', ActivityListScreen::class)
    ->name('platform.audit')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Auditoría', route('platform.audit')));

// Ventas: reporte de ganancias
Route::screen('ventas/report', VentasProfitReportScreen::class)
    ->name('platform.ventas.report')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Reporte de ganancias', route('platform.ventas.report')));

// Reporte del inventario
Route::get('inventario/report', [\App\Orchid\Screens\Inventario\InventarioReportScreen::class, 'export'])
    ->name('platform.inventario.report');

// Alertas de Stock
Route::screen('inventario/low-stock', LowStockScreen::class)
    ->name('platform.inventario.low_stock')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Alertas de Stock'));

// Decisiones y Tendencias (ML)
Route::screen('recommender/decisiones', MLDecisionReportScreen::class)
    ->name('platform.decisiones')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Decisiones (IA)'));

Route::get('recommender/decisiones/report', [MLDecisionReportScreen::class, 'export'])
    ->name('platform.decisiones.report');

// Descarga de backups (GET) - definir ANTES para evitar colisión con Screen route
Route::get('backup/download', [BackupScreen::class, 'download'])->name('platform.backup.download');

// Backups (pantalla principal)
Route::screen('backup', BackupScreen::class)
    ->name('platform.backup')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Backups', route('platform.backup')));

// Experiencias 3D (dentro del panel admin)
Route::screen('three/menu', \App\Orchid\Screens\ThreeMenuScreen::class)
    ->name('platform.three.menu')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Experiencia 3D', route('platform.three.menu')));

Route::screen('three/demo', ThreeDemoScreen::class)
    ->name('platform.three.demo')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Experiencia 3D (Demo)', route('platform.three.demo')));

Route::screen('three/room', ThreeRoomScreen::class)
    ->name('platform.three.room')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Experiencia 3D (Room)', route('platform.three.room')));
