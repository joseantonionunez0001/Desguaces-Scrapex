<?php

namespace Tests\Feature\Api;

use App\Models\Desguace;
use App\Models\RegistroSistema;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AutorizacionApiTest extends TestCase
{
    use RefreshDatabase, PreparaAutorizacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
    }

    private function datosVehiculo(Desguace $desguace): array
    {
        return [
            'id_desguace' => $desguace->id_desguace,
            'matricula' => fake()->unique()->regexify('[0-9]{4}[BCDFGHJKLMNPRSTVWXYZ]{3}'),
            'marca' => 'SEAT',
            'modelo' => 'Ibiza',
            'fecha_llegada' => '2026-01-15',
            'numero_bastidor' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'estado_tratamiento' => 'Pendiente',
        ];
    }

    // ---------- Autenticación ----------

    public function test_sin_token_la_api_devuelve_401(): void
    {
        $this->getJson('/api/vehiculos')->assertStatus(401);
        $this->postJson('/api/vehiculos', [])->assertStatus(401);
        $this->getJson('/api/usuarios')->assertStatus(401);
        $this->getJson('/api/registro-sistema')->assertStatus(401);
    }

    public function test_token_invalido_devuelve_401(): void
    {
        $this->withHeader('Authorization', 'Bearer token-falso')
            ->getJson('/api/vehiculos')
            ->assertStatus(401);
    }

    public function test_login_con_credenciales_correctas_devuelve_token(): void
    {
        $usuario = User::factory()->create(['password' => 'secreto-largo-123']);
        $usuario->assignRole('operario');

        $respuesta = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'secreto-largo-123',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonStructure(['data' => ['token', 'usuario']]);

        $this->assertStringNotContainsString('password', $respuesta->getContent());
    }

    public function test_login_con_contrasena_incorrecta_devuelve_401(): void
    {
        $usuario = User::factory()->create(['password' => 'secreto-largo-123']);

        $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'otra-contrasena',
        ])->assertStatus(401);
    }

    public function test_logout_revoca_el_token(): void
    {
        $usuario = User::factory()->create(['password' => 'secreto-largo-123']);
        $usuario->assignRole('operario');

        $token = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'secreto-largo-123',
        ])->json('data.token');

        $this->withToken($token)->getJson('/api/user')->assertOk();

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // El mismo token ya no sirve.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
    }

    // ---------- Autorización por rol ----------

    public function test_usuario_sin_rol_no_puede_usar_los_crud(): void
    {
        $this->actuarComo(null);

        $this->getJson('/api/vehiculos')->assertStatus(403);
        $this->postJson('/api/piezas', [])->assertStatus(403);
    }

    public function test_operario_puede_consultar_pero_no_crear_ni_borrar(): void
    {
        $this->actuarComo('operario');
        $desguace = Desguace::factory()->create();
        $vehiculo = Vehiculo::factory()->create();

        $this->getJson('/api/vehiculos')->assertOk();
        $this->getJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertOk();

        $this->postJson('/api/vehiculos', $this->datosVehiculo($desguace))->assertStatus(403);
        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($desguace))->assertStatus(403);
    }

    public function test_operario_puede_registrar_movimientos(): void
    {
        $this->actuarComo('operario');

        // Llega al controlador (pasa la autorización) y falla solo por validación.
        $this->postJson('/api/movimientos', [])->assertStatus(422);
    }

    public function test_operario_no_puede_eliminar_piezas(): void
    {
        $this->actuarComo('operario');

        $this->postJson('/api/piezas', [])->assertStatus(403);
    }

    public function test_gerente_puede_crear_y_editar_vehiculos_pero_no_eliminarlos(): void
    {
        $this->actuarComo('gerente');
        $desguace = Desguace::factory()->create();
        $vehiculo = Vehiculo::factory()->create();

        $this->postJson('/api/vehiculos', $this->datosVehiculo($desguace))->assertStatus(201);
        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($desguace))->assertOk();
        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
    }

    public function test_gerente_no_puede_gestionar_usuarios_ni_auditoria(): void
    {
        $this->actuarComo('gerente');
        $objetivo = User::factory()->create();

        $this->getJson('/api/usuarios')->assertStatus(403);
        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'operario'])->assertStatus(403);
        $this->getJson('/api/registro-sistema')->assertStatus(403);
    }

    public function test_administrador_puede_eliminar_vehiculos(): void
    {
        $this->actuarComo('administrador');
        $vehiculo = Vehiculo::factory()->create();

        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertOk();
    }

    // ---------- Gestión de roles ----------

    public function test_administrador_asigna_y_cambia_roles(): void
    {
        $this->actuarComo('administrador');
        $objetivo = User::factory()->create();

        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'operario'])
            ->assertOk();
        $this->assertTrue($objetivo->fresh()->hasRole('operario'));

        // Cambiar el rol sustituye al anterior.
        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'gerente'])
            ->assertOk();
        $objetivo->refresh();
        $this->assertTrue($objetivo->hasRole('gerente'));
        $this->assertFalse($objetivo->hasRole('operario'));
    }

    public function test_administrador_retira_un_rol(): void
    {
        $this->actuarComo('administrador');
        $objetivo = $this->usuarioConRol('operario');

        $this->deleteJson("/api/usuarios/{$objetivo->id}/roles/operario")->assertOk();
        $this->assertFalse($objetivo->fresh()->hasRole('operario'));
    }

    public function test_rol_no_valido_se_rechaza(): void
    {
        $this->actuarComo('administrador');
        $objetivo = User::factory()->create();

        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'superusuario'])
            ->assertStatus(422);
    }

    public function test_nadie_puede_modificar_sus_propios_roles(): void
    {
        $admin = $this->actuarComo('administrador');

        $this->putJson("/api/usuarios/{$admin->id}/rol", ['rol' => 'operario'])
            ->assertStatus(403);
        $this->deleteJson("/api/usuarios/{$admin->id}/roles/administrador")
            ->assertStatus(403);

        $this->assertTrue($admin->fresh()->hasRole('administrador'));
    }

    public function test_no_se_puede_retirar_el_ultimo_administrador(): void
    {
        // Único administrador del sistema.
        $ultimoAdmin = $this->usuarioConRol('administrador');

        // Gestor con permiso de asignar roles (no es admin) que intenta retirar al último admin.
        $gestor = $this->usuarioConRol('operario');
        $gestor->givePermissionTo('usuarios.asignar_roles');
        Sanctum::actingAs($gestor, ['*']);

        $this->deleteJson("/api/usuarios/{$ultimoAdmin->id}/roles/administrador")
            ->assertStatus(422);
        $this->assertTrue($ultimoAdmin->fresh()->hasRole('administrador'));
    }

    public function test_el_registro_de_auditoria_usa_el_usuario_autenticado(): void
    {
        $operador = $this->actuarComo('administrador');

        $this->postJson('/api/registro-sistema', [
            'fecha_hora' => '2026-03-01 10:00:00',
            'id_usuario' => 999999,
        ])->assertStatus(201);

        $this->assertSame($operador->id, RegistroSistema::latest('id_registro')->first()->id_usuario);
    }
}
