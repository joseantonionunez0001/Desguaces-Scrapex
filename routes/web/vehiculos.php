<?php

use App\Http\Controllers\Web\VehiculoController;
use App\Http\Controllers\Web\VehiculoUbicacionController;
use Illuminate\Support\Facades\Route;

/*
 * Vehículos y su historial de ubicaciones.
 * La autorización real se aplica aquí con middleware (igual que en routes/api.php);
 * los @can de las vistas solo ocultan botones.
 */
Route::middleware('auth')->group(function (): void {
    Route::get('vehiculos', [VehiculoController::class, 'index'])
        ->middleware('permission:vehiculos.ver')->name('vehiculos.index');

    // "crear" va antes que {vehiculo} para que no se interprete como identificador.
    Route::get('vehiculos/crear', [VehiculoController::class, 'create'])
        ->middleware('permission:vehiculos.crear')->name('vehiculos.create');
    Route::post('vehiculos', [VehiculoController::class, 'store'])
        ->middleware('permission:vehiculos.crear')->name('vehiculos.store');

    Route::get('vehiculos/{vehiculo}', [VehiculoController::class, 'show'])
        ->whereNumber('vehiculo')
        ->middleware('permission:vehiculos.ver')->name('vehiculos.show');

    Route::get('vehiculos/{vehiculo}/editar', [VehiculoController::class, 'edit'])
        ->whereNumber('vehiculo')
        ->middleware('permission:vehiculos.editar')->name('vehiculos.edit');
    Route::match(['put', 'patch'], 'vehiculos/{vehiculo}', [VehiculoController::class, 'update'])
        ->whereNumber('vehiculo')
        ->middleware('permission:vehiculos.editar')->name('vehiculos.update');

    Route::delete('vehiculos/{vehiculo}', [VehiculoController::class, 'destroy'])
        ->whereNumber('vehiculo')
        ->middleware('permission:vehiculos.eliminar')->name('vehiculos.destroy');

    // Historial de ubicaciones (solo lectura): exige ver vehículos Y ver ubicaciones.
    Route::get('vehiculos/{vehiculo}/ubicaciones', [VehiculoUbicacionController::class, 'index'])
        ->whereNumber('vehiculo')
        ->middleware(['permission:vehiculos.ver', 'permission:ubicaciones_vehiculo.ver'])
        ->name('vehiculos.ubicaciones.index');
});
