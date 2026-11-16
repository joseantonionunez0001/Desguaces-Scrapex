<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehiculoApiController;
use App\Http\Controllers\PiezaApiController;

Route::apiResource('vehiculos', VehiculoApiController::class);
Route::apiResource('piezas', PiezaApiController::class);

Route::post(
    'vehiculos/{id}/vincular/{modo}',
    [VehiculoApiController::class, 'vincularPiezas']
);