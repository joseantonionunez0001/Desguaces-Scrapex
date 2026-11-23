<?php

use App\Http\Controllers\Web\UsuarioController;
use App\Http\Controllers\Web\UsuarioRolController;
use Illuminate\Support\Facades\Route;

// Administración de usuarios y asignación de roles (solo administradores, por permisos de Spatie).
Route::middleware('auth')->prefix('admin/usuarios')->name('admin.usuarios.')->group(function () {
    Route::get('/', [UsuarioController::class, 'index'])
        ->middleware('permission:usuarios.ver')
        ->name('index');

    Route::get('{user}', [UsuarioController::class, 'show'])
        ->middleware('permission:usuarios.ver')
        ->name('show');

    Route::put('{user}/rol', [UsuarioRolController::class, 'update'])
        ->middleware('permission:usuarios.asignar_roles')
        ->name('rol.update');

    Route::delete('{user}/rol', [UsuarioRolController::class, 'destroy'])
        ->middleware('permission:usuarios.asignar_roles')
        ->name('rol.destroy');
});
