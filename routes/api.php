<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesguaceApiController;

// Genera GET, POST, GET/{id}, PUT/PATCH/{id} y DELETE/{id}
Route::apiResource('desguaces', DesguaceApiController::class);
