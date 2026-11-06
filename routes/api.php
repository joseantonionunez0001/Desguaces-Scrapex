<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DesguaceApiController;
use App\Http\Controllers\EstanteriaApiController;
use App\Http\Controllers\EtiquetaApiController;
use App\Http\Controllers\UbicacionVehiculoApiController;
use App\Http\Controllers\RegistroSistemaApiController;

use App\Http\Controllers\VehiculoApiController;
use App\Http\Controllers\CategoriaApiController;
use App\Http\Controllers\TipoPiezaApiController;
use App\Http\Controllers\PiezaApiController;
use App\Http\Controllers\MovimientoApiController;


// CRUD DESGUACE
Route::apiResource('desguaces', DesguaceApiController::class);

// CRUD JOSE ANTONIO
Route::apiResource('estanterias', EstanteriaApiController::class);
Route::apiResource('etiquetas', EtiquetaApiController::class);
Route::apiResource('ubicaciones-vehiculo', UbicacionVehiculoApiController::class);
Route::apiResource('registros-sistema', RegistroSistemaApiController::class);

// CRUD LUIS REYES
Route::apiResource('vehiculos', VehiculoApiController::class);
Route::apiResource('categorias', CategoriaApiController::class);
Route::apiResource('tipo_piezas', TipoPiezaApiController::class);
Route::apiResource('piezas', PiezaApiController::class);
Route::apiResource('movimientos', MovimientoApiController::class);