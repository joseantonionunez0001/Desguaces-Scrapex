<?php

use App\Http\Controllers\Web\CategoriaController;
use Illuminate\Support\Facades\Route;

// Gestión de categorías (el listado incluye los modales de alta y edición).
Route::middleware('auth')->group(function () {
    Route::get('/categorias', [CategoriaController::class, 'index'])
        ->middleware('permission:categorias.ver')
        ->name('categorias.index');

    Route::post('/categorias', [CategoriaController::class, 'store'])
        ->middleware('permission:categorias.crear')
        ->name('categorias.store');

    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])
        ->middleware('permission:categorias.editar')
        ->name('categorias.update');

    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])
        ->middleware('permission:categorias.eliminar')
        ->name('categorias.destroy');
});
