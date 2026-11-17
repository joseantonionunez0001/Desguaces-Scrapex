<?php

use App\Http\Controllers\Web\DesguaceController;
use Illuminate\Support\Facades\Route;

// Gestión de desguaces: crear, editar y eliminar solo lo puede hacer el administrador.
Route::middleware('auth')->group(function () {
    Route::get('/desguaces', [DesguaceController::class, 'index'])
        ->middleware('permission:desguaces.ver')
        ->name('desguaces.index');

    Route::post('/desguaces', [DesguaceController::class, 'store'])
        ->middleware('permission:desguaces.crear')
        ->name('desguaces.store');

    Route::put('/desguaces/{desguace}', [DesguaceController::class, 'update'])
        ->middleware('permission:desguaces.editar')
        ->name('desguaces.update');

    Route::delete('/desguaces/{desguace}', [DesguaceController::class, 'destroy'])
        ->middleware('permission:desguaces.eliminar')
        ->name('desguaces.destroy');
});
