<?php

use App\Http\Controllers\Web\AlmacenExteriorController;
use App\Http\Controllers\Web\AlmacenInteriorController;
use App\Services\EstructuraAlmacen;
use Illuminate\Support\Facades\Route;

/*
 * Gestión del almacén (pantallas 17 y 18).
 *
 * Cada nivel de la jerarquía tiene sus propias rutas de alta, edición y baja con el permiso
 * del recurso (modulos.crear, patios.editar…). El tipo viaja como valor por defecto de la ruta.
 * Las pantallas se abren con el permiso de consulta del primer nivel (modulos.ver / patios.ver).
 */
Route::middleware('auth')->prefix('almacen')->name('almacen.')->group(function (): void {
    $zonas = [
        EstructuraAlmacen::INTERIOR => [AlmacenInteriorController::class, 'modulos.ver'],
        EstructuraAlmacen::EXTERIOR => [AlmacenExteriorController::class, 'patios.ver'],
    ];

    foreach ($zonas as $zona => [$controlador, $permisoDeAcceso]) {
        Route::prefix($zona)->name("{$zona}.")->group(function () use ($zona, $controlador, $permisoDeAcceso): void {
            Route::get('/', [$controlador, 'index'])
                ->middleware("permission:{$permisoDeAcceso}")
                ->name('index');

            foreach (EstructuraAlmacen::tipos($zona) as $tipo) {
                Route::post($tipo, [$controlador, 'store'])
                    ->defaults('tipo', $tipo)
                    ->middleware("permission:{$tipo}.crear")
                    ->name("{$tipo}.store");

                Route::put("{$tipo}/{id}", [$controlador, 'update'])
                    ->defaults('tipo', $tipo)
                    ->whereNumber('id')
                    ->middleware("permission:{$tipo}.editar")
                    ->name("{$tipo}.update");

                Route::delete("{$tipo}/{id}", [$controlador, 'destroy'])
                    ->defaults('tipo', $tipo)
                    ->whereNumber('id')
                    ->middleware("permission:{$tipo}.eliminar")
                    ->name("{$tipo}.destroy");
            }
        });
    }
});
