<?php

namespace Tests\Support;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Estanteria;
use App\Models\Etiqueta;
use App\Models\Fila;
use App\Models\Marca;
use App\Models\ModeloVehiculo;
use App\Models\Modulo;
use App\Models\Movimiento;
use App\Models\Nivel;
use App\Models\Pasillo;
use App\Models\Patio;
use App\Models\Pieza;
use App\Models\Posicion;
use App\Models\Sector;
use App\Models\TipoPieza;
use App\Models\UbicacionVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Factories\DesguaceFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Utilidades de autorizacion para las pruebas.
 * Los roles y permisos se crean con los mismos seeders que usa la aplicacion.
 */
trait PreparaAutorizacion
{
    protected function prepararRoles(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    /**
     * Cuenta con el rol indicado. Los roles de sede (administrador de desguace, gerente y operario)
     * reciben la sede indicada o, si no se indica, la sede por defecto de las factories.
     */
    protected function usuarioConRol(?string $rol = null, ?Desguace $sede = null): User
    {
        if ($sede === null && in_array($rol, RoleSeeder::ROLES_DESGUACE, true)) {
            $sede = Desguace::query()->withoutGlobalScopes()->findOrFail(DesguaceFactory::idPorDefecto());
        }

        $usuario = User::factory()->create(['id_desguace' => $sede?->id_desguace]);

        if ($rol !== null) {
            $usuario->assignRole($rol);
        }

        return $usuario;
    }

    /**
     * Cuenta con un rol de sede pero SIN desguace asignado (acceso bloqueado).
     */
    protected function usuarioSinSede(string $rol): User
    {
        $usuario = User::factory()->create(['id_desguace' => null]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    // Autentica las peticiones JSON siguientes con un token de Sanctum.
    protected function actuarComo(?string $rol = null, ?Desguace $sede = null): User
    {
        $usuario = $this->usuarioConRol($rol, $sede);
        Sanctum::actingAs($usuario, ['*']);

        return $usuario;
    }

    // Crea una sede y la propiedad $this->sede para usarla en la prueba.
    protected function crearSede(): Desguace
    {
        return Desguace::factory()->create();
    }

    /**
     * Crea el inventario completo de una sede usando las relaciones reales:
     * patio > sector > fila > posicion > nivel, modulo, vehiculo, categoria > tipo de pieza > pieza y movimiento.
     *
     * @return array<string, mixed>
     */
    protected function inventarioDe(Desguace $sede): array
    {
        $id = $sede->id_desguace;

        $modulo = Modulo::factory()->create(['id_desguace' => $id]);
        $pasillo = Pasillo::factory()->create(['id_modulo' => $modulo->id_modulo]);
        $estanteria = Estanteria::factory()->create(['id_pasillo' => $pasillo->id_pasillo]);
        $etiqueta = Etiqueta::factory()->create(['id_estanteria' => $estanteria->id_estanteria]);
        $patio = Patio::factory()->create(['id_desguace' => $id]);
        $sector = Sector::factory()->create(['id_patio' => $patio->id_patio]);
        $fila = Fila::factory()->create(['id_sector' => $sector->id_sector]);
        $posicion = Posicion::factory()->create(['id_fila' => $fila->id_fila]);
        $nivel = Nivel::factory()->create(['id_posicion' => $posicion->id_posicion]);
        $vehiculo = Vehiculo::factory()->create(['id_desguace' => $id]);
        $ubicacion = UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
        ]);
        $categoria = Categoria::factory()->create([
            'id_desguace' => $id,
            'id_modulo' => $modulo->id_modulo,
            'id_vehiculo' => null,
        ]);
        $tipo = TipoPieza::factory()->create(['id_categoria' => $categoria->categoria_id]);
        $autor = User::factory()->create(['id_desguace' => $id]);
        $pieza = Pieza::factory()->create([
            'id_usuario' => $autor->id,
            'id_tipo_pieza' => $tipo->id_pieza,
            'stock' => 5,
        ]);
        $movimiento = Movimiento::factory()->create(['id_categoria' => $categoria->categoria_id]);

        return compact(
            'modulo', 'pasillo', 'estanteria', 'etiqueta', 'patio', 'sector', 'fila', 'posicion', 'nivel', 'ubicacion',
            'vehiculo', 'categoria', 'tipo', 'autor', 'pieza', 'movimiento'
        );
    }

    /**
     * Hueco del almacén interior (módulo > pasillo > estantería > etiqueta) en la sede indicada
     * o en la sede por defecto.
     */
    protected function hueco(string $etiqueta = 'A-01', ?Desguace $sede = null, string $modulo = 'Módulo A'): Etiqueta
    {
        $idDesguace = $sede?->id_desguace ?? DesguaceFactory::idPorDefecto();
        $moduloCreado = Modulo::query()->withoutGlobalScopes()->firstOrCreate(
            ['id_desguace' => $idDesguace, 'nombre_modulo' => $modulo],
        );
        $pasillo = Pasillo::factory()->create(['id_modulo' => $moduloCreado->id_modulo, 'numero_pasillo' => '1']);
        $estanteria = Estanteria::factory()->create(['id_pasillo' => $pasillo->id_pasillo, 'numero_estanteria' => '2']);

        return Etiqueta::factory()->create(['id_estanteria' => $estanteria->id_estanteria, 'numero_etiqueta' => $etiqueta]);
    }

    /**
     * Modelo de vehículo del catálogo común (con su marca).
     */
    protected function modeloDeVehiculo(string $marca = 'Seat', string $modelo = 'Ibiza'): ModeloVehiculo
    {
        return Marca::registrarModelo($marca, $modelo);
    }

    // Datos validos para crear un vehiculo en una sede.
    protected function datosVehiculo(Desguace $sede): array
    {
        return [
            'id_desguace' => $sede->id_desguace,
            'matricula' => fake()->unique()->regexify('[0-9]{4}[BCDFGHJKLMNPRSTVWXYZ]{3}'),
            'marca' => 'SEAT',
            'modelo' => 'Ibiza',
            'fecha_llegada' => '2026-01-15',
            'numero_bastidor' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'estado_tratamiento' => 'Pendiente',
        ];
    }
}
