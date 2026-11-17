<?php

use App\Http\Controllers\Web\RegistroSistemaController;
use App\Http\Controllers\Web\RolesPermisosController;
use Illuminate\Support\Facades\Route;

/*
 * Administración (pantallas 20 y 21): solo consulta, sin rutas de modificación ni de borrado.
 */
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('roles-permisos', [RolesPermisosController::class, 'index'])
        ->middleware('permission:usuarios.asignar_roles')
        ->name('roles-permisos.index');

    Route::get('registros', [RegistroSistemaController::class, 'index'])
        ->middleware('permission:registro_sistema.ver')
        ->name('registros.index');
});
