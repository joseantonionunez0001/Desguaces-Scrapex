<?php

use App\Http\Controllers\Web\UsuarioController;
use App\Http\Controllers\Web\UsuarioRolController;
use Illuminate\Support\Facades\Route;

/*
 * Administración de usuarios.
 * - Administrador de desguace: alta, edición, baja y rol de los gerentes y operarios de su sede.
 * - Administrador de plataforma: consulta de cuentas y cambio de sede.
 * Los permisos se comprueban aquí; el alcance por sede, en el controlador.
 */
Route::middleware('auth')->prefix('admin/usuarios')->name('admin.usuarios.')->group(function () {
    Route::get('/', [UsuarioController::class, 'index'])
        ->middleware('permission:usuarios.ver')
        ->name('index');

    // "crear" va antes que {user} para que no se interprete como identificador.
    Route::get('crear', [UsuarioController::class, 'create'])
        ->middleware('permission:usuarios.crear')
        ->name('create');

    Route::post('/', [UsuarioController::class, 'store'])
        ->middleware('permission:usuarios.crear')
        ->name('store');

    Route::get('{user}', [UsuarioController::class, 'show'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.ver')
        ->name('show');

    Route::get('{user}/editar', [UsuarioController::class, 'edit'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.editar')
        ->name('edit');

    Route::put('{user}', [UsuarioController::class, 'update'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.editar')
        ->name('update');

    Route::delete('{user}', [UsuarioController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.eliminar')
        ->name('destroy');

    Route::put('{user}/desguace', [UsuarioController::class, 'asignarDesguace'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.asignar_desguace')
        ->name('desguace.update');

    Route::put('{user}/rol', [UsuarioRolController::class, 'update'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.asignar_roles')
        ->name('rol.update');

    Route::delete('{user}/rol', [UsuarioRolController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware('permission:usuarios.asignar_roles')
        ->name('rol.destroy');
});
