<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\CategoriaApiController;
use App\Http\Controllers\Api\DesguaceApiController;
use App\Http\Controllers\Api\EstanteriaApiController;
use App\Http\Controllers\Api\EtiquetaApiController;
use App\Http\Controllers\Api\FilaApiController;
use App\Http\Controllers\Api\ModuloApiController;
use App\Http\Controllers\Api\MovimientoApiController;
use App\Http\Controllers\Api\NivelApiController;
use App\Http\Controllers\Api\PasilloApiController;
use App\Http\Controllers\Api\PatioApiController;
use App\Http\Controllers\Api\PiezaApiController;
use App\Http\Controllers\Api\PosicionApiController;
use App\Http\Controllers\Api\RegistroSistemaApiController;
use App\Http\Controllers\Api\SectorApiController;
use App\Http\Controllers\Api\TipoPiezaApiController;
use App\Http\Controllers\Api\UbicacionVehiculoApiController;
use App\Http\Controllers\Api\UsuarioApiController;
use App\Http\Controllers\Api\VehiculoApiController;
use Illuminate\Support\Facades\Route;

// Login de la API: devuelve un token de Sanctum. Límite de intentos por IP.
Route::post('login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1');

// Todo lo demás requiere un token válido (401 si no se envía o no es válido).
Route::middleware('auth:sanctum')->group(function () {

    Route::post('logout', [ApiAuthController::class, 'logout']);
    Route::get('user', [ApiAuthController::class, 'me']);

    // Recurso => [controlador, prefijo de permisos].
    // Cada operación exige "<prefijo>.<accion>" (ver, crear, editar, eliminar).
    $recursos = [
        'desguaces' => [DesguaceApiController::class, 'desguaces'],
        'modulos' => [ModuloApiController::class, 'modulos'],
        'pasillos' => [PasilloApiController::class, 'pasillos'],
        'estanterias' => [EstanteriaApiController::class, 'estanterias'],
        'etiquetas' => [EtiquetaApiController::class, 'etiquetas'],
        'patios' => [PatioApiController::class, 'patios'],
        'sectores' => [SectorApiController::class, 'sectores'],
        'filas' => [FilaApiController::class, 'filas'],
        'posiciones' => [PosicionApiController::class, 'posiciones'],
        'niveles' => [NivelApiController::class, 'niveles'],
        'vehiculos' => [VehiculoApiController::class, 'vehiculos'],
        'ubicaciones-vehiculo' => [UbicacionVehiculoApiController::class, 'ubicaciones_vehiculo'],
        'categorias' => [CategoriaApiController::class, 'categorias'],
        'tipo-piezas' => [TipoPiezaApiController::class, 'tipo_piezas'],
        'piezas' => [PiezaApiController::class, 'piezas'],
        'movimientos' => [MovimientoApiController::class, 'movimientos'],
    ];

    foreach ($recursos as $ruta => [$controlador, $permiso]) {
        Route::get($ruta, [$controlador, 'index'])->middleware("permission:{$permiso}.ver");
        Route::post($ruta, [$controlador, 'store'])->middleware("permission:{$permiso}.crear");
        Route::get("{$ruta}/{id}", [$controlador, 'show'])->middleware("permission:{$permiso}.ver");
        Route::match(['put', 'patch'], "{$ruta}/{id}", [$controlador, 'update'])->middleware("permission:{$permiso}.editar");
        Route::delete("{$ruta}/{id}", [$controlador, 'destroy'])->middleware("permission:{$permiso}.eliminar");
    }

    // Usuarios: solo administradores (permisos usuarios.*).
    Route::get('usuarios', [UsuarioApiController::class, 'index'])->middleware('permission:usuarios.ver');
    Route::post('usuarios', [UsuarioApiController::class, 'store'])->middleware('permission:usuarios.crear');
    Route::get('usuarios/{id}', [UsuarioApiController::class, 'show'])->middleware('permission:usuarios.ver');
    Route::match(['put', 'patch'], 'usuarios/{id}', [UsuarioApiController::class, 'update'])->middleware('permission:usuarios.editar');
    Route::delete('usuarios/{id}', [UsuarioApiController::class, 'destroy'])->middleware('permission:usuarios.eliminar');
    Route::put('usuarios/{id}/rol', [UsuarioApiController::class, 'asignarRol'])->middleware('permission:usuarios.asignar_roles');
    Route::delete('usuarios/{id}/roles/{rol}', [UsuarioApiController::class, 'retirarRol'])->middleware('permission:usuarios.asignar_roles');

    // Registro de sistema (auditoría): solo lectura y alta. Nunca se modifica ni se borra.
    Route::get('registro-sistema', [RegistroSistemaApiController::class, 'index'])->middleware('permission:registro_sistema.ver');
    Route::get('registro-sistema/{id}', [RegistroSistemaApiController::class, 'show'])->middleware('permission:registro_sistema.ver');
    Route::post('registro-sistema', [RegistroSistemaApiController::class, 'store'])->middleware('permission:registro_sistema.crear');
});
