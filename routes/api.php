<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesguaceApiController;
use App\Http\Controllers\EstanteriaApiController;
use App\Http\Controllers\EtiquetaApiController;
use App\Http\Controllers\UbicacionVehiculoApiController;
use App\Http\Controllers\RegistroSistemaApiController;

// Genera GET, POST, GET/{id}, PUT/PATCH/{id} y DELETE/{id}
Route::apiResource('desguaces', DesguaceApiController::class);
Route::apiResource('estanterias', EstanteriaApiController::class);
Route::apiResource('etiquetas', EtiquetaApiController::class);
Route::apiResource('ubicaciones-vehiculo', UbicacionVehiculoApiController::class);
Route::apiResource('registros-sistema', RegistroSistemaApiController::class);
