<?php

namespace Tests\Feature\Api;

use App\Models\Desguace;
use App\Models\Nivel;
use App\Models\UbicacionVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * CRUD y relaciones de la API. Las peticiones se hacen como administrador,
 * para comprobar el funcionamiento del backend. Los permisos por rol están en AutorizacionApiTest.
 */
class DesguaceApiTest extends TestCase
{
    use RefreshDatabase, PreparaAutorizacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
        $this->actuarComo('administrador');
    }

    public function test_el_seeder_crea_datos_relacionados(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('desguaces', 2);
        $this->assertDatabaseCount('vehiculos', 8);
        // 8 vehículos x 2 ubicaciones (historial cerrado y abierto).
        $this->assertDatabaseCount('ubicaciones_vehiculo', 16);
    }

    public function test_crud_de_vehiculos(): void
    {
        $desguace = Desguace::factory()->create();

        $crear = $this->postJson('/api/vehiculos', [
            'id_desguace' => $desguace->id_desguace,
            'matricula' => '1234ABC',
            'marca' => 'SEAT',
            'modelo' => 'Ibiza',
            'fecha_llegada' => '2026-01-15',
            'numero_bastidor' => 'VSSZZZ6JZ1R000001',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $crear->assertStatus(201)
            ->assertJsonPath('success', 1)
            ->assertJsonPath('data.matricula', '1234ABC');

        $id = $crear->json('data.id_vehiculo');

        $this->getJson("/api/vehiculos/{$id}")
            ->assertOk()
            ->assertJsonPath('data.marca', 'SEAT');

        $this->putJson("/api/vehiculos/{$id}", [
            'id_desguace' => $desguace->id_desguace,
            'matricula' => '1234ABC',
            'marca' => 'SEAT',
            'modelo' => 'Leon',
            'fecha_llegada' => '2026-01-15',
            'numero_bastidor' => 'VSSZZZ6JZ1R000001',
            'estado_tratamiento' => 'Descontaminado',
        ])->assertOk()->assertJsonPath('data.modelo', 'Leon');

        $this->deleteJson("/api/vehiculos/{$id}")->assertOk();

        // Borrado lógico: el registro sigue en la base de datos.
        $this->assertSoftDeleted('vehiculos', ['id_vehiculo' => $id]);
        $this->getJson("/api/vehiculos/{$id}")->assertNotFound();
    }

    public function test_validacion_devuelve_422(): void
    {
        $this->postJson('/api/vehiculos', [])
            ->assertStatus(422);
    }

    public function test_registro_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/vehiculos/999999')
            ->assertNotFound();
    }

    public function test_clave_foranea_inexistente_se_rechaza(): void
    {
        $this->postJson('/api/modulos', [
            'id_desguace' => 999999,
            'nombre_modulo' => 'Modulo A',
        ])->assertStatus(422);
    }

    public function test_el_historial_de_ubicaciones_del_vehiculo_es_consultable(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $nivel = Nivel::factory()->create();

        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-02-01',
        ]);
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-02-02',
            'fecha_fin' => null,
        ]);

        $this->assertCount(2, $vehiculo->fresh()->ubicaciones);
    }

    public function test_una_misma_posicion_puede_reutilizarse(): void
    {
        $vehiculo1 = Vehiculo::factory()->create();
        $vehiculo2 = Vehiculo::factory()->create();
        $nivel = Nivel::factory()->create();

        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo1->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-10',
        ]);

        $this->postJson('/api/ubicaciones-vehiculo', [
            'id_vehiculo' => $vehiculo2->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-11',
        ])->assertStatus(201);
    }

    public function test_el_campo_ubicacion_del_desguace_se_guarda_con_tilde(): void
    {
        $this->postJson('/api/desguaces', [
            'nombre' => 'Desguace Central',
            'direccion' => 'Calle Mayor 1',
            'numero_telefono' => '912345678',
            'ubicación' => 'Polígono Industrial',
        ])->assertStatus(201)
            ->assertJsonPath('data.ubicación', 'Polígono Industrial');
    }

    public function test_el_listado_de_usuarios_no_expone_password(): void
    {
        User::factory()->create();

        $respuesta = $this->getJson('/api/usuarios')->assertOk();

        $this->assertStringNotContainsString('password', $respuesta->getContent());
        $this->assertStringNotContainsString('remember_token', $respuesta->getContent());
    }

    public function test_el_password_del_usuario_no_se_serializa(): void
    {
        $usuario = User::factory()->create();

        $this->assertArrayNotHasKey('password', $usuario->toArray());
        $this->assertArrayNotHasKey('remember_token', $usuario->toArray());
    }
}
