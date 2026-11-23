<?php

use App\Http\Controllers\Web\AsignacionUbicacionController;
use Illuminate\Support\Facades\Route;

/*
 * Asignación de ubicaciones de vehículos (pantalla 19).
 *
 * Asignar o trasladar crea una ubicación nueva y cierra la vigente, por lo que se exigen
 * los permisos de crear y de editar ubicaciones (el operario no los tiene).
 */
Route::middleware(['auth', 'permission:ubicaciones_vehiculo.crear', 'permission:ubicaciones_vehiculo.editar'])
    ->prefix('vehiculos/{vehiculo}/asignar-ubicacion')
    ->whereNumber('vehiculo')
    ->group(function (): void {
        Route::get('/', [AsignacionUbicacionController::class, 'create'])->name('vehiculos.ubicaciones.asignar');
        Route::post('/', [AsignacionUbicacionController::class, 'store'])->name('vehiculos.ubicaciones.store');
    });
