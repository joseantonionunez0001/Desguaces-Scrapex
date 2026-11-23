<?php

use App\Http\Controllers\Web\MovimientoController;
use Illuminate\Support\Facades\Route;

/*
 * Movimientos de inventario. Cada uno se registra sobre una pieza concreta.
 * El historial es de solo lectura: no hay rutas de edición ni de borrado.
 * "/movimientos/crear" va antes que cualquier ruta con parámetro.
 */
Route::middleware('auth')->group(function () {
    Route::get('/movimientos', [MovimientoController::class, 'index'])
        ->middleware('permission:movimientos.ver')
        ->name('movimientos.index');

    Route::get('/movimientos/crear', [MovimientoController::class, 'create'])
        ->middleware('permission:movimientos.crear')
        ->name('movimientos.create');

    // El permiso exacto (registrar movimientos o retirar la ficha) lo comprueba MovimientoRequest.
    Route::post('/piezas/{pieza}/movimientos', [MovimientoController::class, 'store'])
        ->whereNumber('pieza')
        ->middleware('permission:movimientos.crear')
        ->name('movimientos.store');
});
