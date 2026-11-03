<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehiculoApiController;
use App\Http\Controllers\CategoriaApiController;
use App\Http\Controllers\TipoPiezaApiController;
use App\Http\Controllers\PiezaApiController;
use App\Http\Controllers\MovimientoApiController;

// Genera GET, POST, GET/{id}, PUT/PATCH/{id} y DELETE/{id}
Route::apiResource('vehiculos', VehiculoApiController::class);
Route::apiResource('categorias', CategoriaApiController::class);
Route::apiResource('tipo_piezas', TipoPiezaApiController::class);
Route::apiResource('piezas', PiezaApiController::class);
Route::apiResource('movimientos', MovimientoApiController::class);
