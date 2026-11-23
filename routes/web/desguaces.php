<?php

use App\Http\Controllers\Web\DesguaceController;
use Illuminate\Support\Facades\Route;

// Gestión de desguaces: crear, editar, eliminar y designar administrador es tarea del administrador de plataforma.
Route::middleware('auth')->group(function () {
    Route::put('/desguaces/{desguace}/administrador', [DesguaceController::class, 'asignarAdministrador'])
        ->middleware('permission:desguaces.asignar_administrador')
        ->name('desguaces.administrador.update');

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
