<?php

use App\Http\Controllers\Web\PiezaController;
use Illuminate\Support\Facades\Route;

/*
 * Catálogo de piezas y existencias.
 * La autorización real se aplica aquí con middleware (igual que en routes/api.php).
 *
 * No hay ruta de borrado directo: retirar una pieza es la acción «Eliminar pieza» de Movimientos,
 * que registra la baja y conserva la ficha archivada con su historial.
 */
Route::middleware('auth')->group(function (): void {
    Route::get('piezas', [PiezaController::class, 'index'])
        ->middleware('permission:piezas.ver')->name('piezas.index');

    // "crear" va antes que {pieza} para que no se interprete como identificador.
    Route::get('piezas/crear', [PiezaController::class, 'create'])
        ->middleware(['permission:piezas.crear', 'permission:movimientos.crear'])->name('piezas.create');

    // El alta registra también la Entrada inicial: exige crear piezas y registrar movimientos.
    Route::post('piezas', [PiezaController::class, 'store'])
        ->middleware(['permission:piezas.crear', 'permission:movimientos.crear'])->name('piezas.store');

    Route::get('piezas/{pieza}', [PiezaController::class, 'show'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.ver')->name('piezas.show');

    Route::get('piezas/{pieza}/editar', [PiezaController::class, 'edit'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.editar')->name('piezas.edit');

    Route::match(['put', 'patch'], 'piezas/{pieza}', [PiezaController::class, 'update'])
        ->whereNumber('pieza')
        ->middleware('permission:piezas.editar')->name('piezas.update');
});
