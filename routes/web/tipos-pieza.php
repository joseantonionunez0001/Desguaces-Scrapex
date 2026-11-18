<?php

use App\Http\Controllers\Web\TipoPiezaController;
use Illuminate\Support\Facades\Route;

// Gestión de tipos de pieza. La clave primaria del modelo es "id_pieza", por eso el parámetro
// de ruta se llama {tipoPieza} y no {pieza} (que pertenece al catálogo de piezas).
Route::middleware('auth')->group(function () {

    Route::post('/tipos-pieza', [TipoPiezaController::class, 'store'])
        ->middleware('permission:tipo_piezas.crear')
        ->name('tipos-pieza.store');

    Route::put('/tipos-pieza/{tipoPieza}', [TipoPiezaController::class, 'update'])
        ->middleware('permission:tipo_piezas.editar')
        ->name('tipos-pieza.update');

    Route::delete('/tipos-pieza/{tipoPieza}', [TipoPiezaController::class, 'destroy'])
        ->middleware('permission:tipo_piezas.eliminar')
        ->name('tipos-pieza.destroy');
});
