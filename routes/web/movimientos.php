<?php

use App\Http\Controllers\Web\MovimientoController;
use Illuminate\Support\Facades\Route;

// Movimientos de inventario. "/movimientos/crear" va antes que cualquier ruta con parámetro.
Route::middleware('auth')->group(function () {
    Route::get('/movimientos', [MovimientoController::class, 'index'])
        ->middleware('permission:movimientos.ver')
        ->name('movimientos.index');

    Route::get('/movimientos/crear', [MovimientoController::class, 'create'])
        ->middleware('permission:movimientos.crear')
        ->name('movimientos.create');

    Route::post('/movimientos', [MovimientoController::class, 'store'])
        ->middleware('permission:movimientos.crear')
        ->name('movimientos.store');

    Route::delete('/movimientos/{movimiento}', [MovimientoController::class, 'destroy'])
        ->middleware('permission:movimientos.eliminar')
        ->name('movimientos.destroy');
});
