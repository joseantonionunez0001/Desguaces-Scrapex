<?php

use App\Http\Controllers\Web\PiezaController;
use Illuminate\Support\Facades\Route;

/*
 * Catálogo de piezas y existencias.
 * La autorización real se aplica aquí con middleware (igual que en routes/api.php).
 */
Route::middleware('auth')->group(function (): void {
    Route::get('piezas', [PiezaController::class, 'index'])
        ->middleware('permission:piezas.ver')->name('piezas.index');

    // "crear" va antes que {pieza} para que no se interprete como identificador.
    Route::get('piezas/crear', [PiezaController::class, 'create'])
        ->middleware('permission:piezas.crear')->name('piezas.create');
    Route::post('piezas', [PiezaController::class, 'store'])
        ->middleware('permission:piezas.crear')->name('piezas.store');

    Route::get('piezas/{pieza}', [PiezaController::class, 'show'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.ver')->name('piezas.show');

    Route::get('piezas/{pieza}/editar', [PiezaController::class, 'edit'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.editar')->name('piezas.edit');
    Route::match(['put', 'patch'], 'piezas/{pieza}', [PiezaController::class, 'update'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.editar')->name('piezas.update');

    Route::delete('piezas/{pieza}', [PiezaController::class, 'destroy'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.eliminar')->name('piezas.destroy');
});
