<?php

namespace Tests\Feature\Api;

use App\Models\Desguace;
use App\Models\RegistroSistema;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AutorizacionApiTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
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

    // ---------- Autorizacion por rol y desguace ----------

    public function test_usuario_sin_rol_no_puede_usar_los_crud(): void
    {
        $this->actuarComo(null);

        $this->getJson('/api/vehiculos')->assertStatus(403);
        $this->postJson('/api/piezas', [])->assertStatus(403);
    }

    public function test_rol_de_desguace_sin_sede_queda_bloqueado_con_aviso(): void
    {
        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::GERENTE, RoleSeeder::OPERARIO] as $rol) {
            Sanctum::actingAs($this->usuarioSinSede($rol), ['*']);

            $this->getJson('/api/vehiculos')
                ->assertStatus(403)
                ->assertJsonPath('data', 'Tu cuenta no tiene un desguace asignado. Contacta con administración.');
            $this->getJson('/api/usuarios')->assertStatus(403);
        }
    }

    public function test_una_sede_eliminada_bloquea_a_su_personal(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::GERENTE, $sede);
        $sede->delete();

        $this->getJson('/api/vehiculos')->assertStatus(403);
    }

    public function test_operario_puede_consultar_pero_no_crear_ni_borrar(): void
    {
        $sede = $this->crearSede();
        $vehiculo = $this->inventarioDe($sede)['vehiculo'];
        $this->actuarComo(RoleSeeder::OPERARIO, $sede);

        $this->getJson('/api/vehiculos')->assertOk();
        $this->getJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertOk();
        $this->postJson('/api/vehiculos', $this->datosVehiculo($sede))->assertStatus(403);
        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($sede))->assertStatus(403);
    }

    public function test_operario_no_puede_registrar_movimientos_ni_tocar_piezas_o_stock(): void
    {
        $sede = $this->crearSede();
        $inventario = $this->inventarioDe($sede);
        $this->actuarComo(RoleSeeder::OPERARIO, $sede);

        $this->postJson('/api/movimientos', [])->assertStatus(403);
        $this->postJson('/api/piezas', [])->assertStatus(403);
        $this->putJson("/api/piezas/{$inventario['pieza']->id_pieza}", ['stock' => 99])->assertStatus(403);
        $this->deleteJson("/api/piezas/{$inventario['pieza']->id_pieza}")->assertStatus(403);
        $this->assertSame(5, $inventario['pieza']->fresh()->stock);
    }

    public function test_operario_no_accede_a_modulos_de_gestion(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::OPERARIO, $sede);

        foreach (['modulos', 'patios', 'pasillos', 'sectores', 'movimientos', 'usuarios', 'registro-sistema'] as $ruta) {
            $this->getJson("/api/{$ruta}")->assertStatus(403);
        }
    }

    public function test_gerente_puede_crear_y_editar_vehiculos_pero_no_eliminarlos(): void
    {
        $sede = $this->crearSede();
        $vehiculo = $this->inventarioDe($sede)['vehiculo'];
        $this->actuarComo(RoleSeeder::GERENTE, $sede);

        $this->postJson('/api/vehiculos', $this->datosVehiculo($sede))->assertStatus(201);
        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($sede))->assertOk();
        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
    }

    public function test_gerente_gestiona_piezas_y_stock_de_su_sede(): void
    {
        $sede = $this->crearSede();
        $inventario = $this->inventarioDe($sede);
        $gerente = $this->actuarComo(RoleSeeder::GERENTE, $sede);
        $pieza = $inventario['pieza'];

        $this->putJson("/api/piezas/{$pieza->id_pieza}", [
            'nombre' => 'Alternador',
            'precio' => 50,
            'estado' => 'Usada',
            'stock' => 12,
            'stock_minimo' => 2,
            'id_usuario' => $gerente->id,
            'id_tipo_pieza' => $inventario['tipo']->id_pieza,
        ])->assertOk();

        $this->assertSame(12, $pieza->fresh()->stock);
    }

    public function test_gerente_no_puede_gestionar_usuarios_ni_auditoria(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::GERENTE, $sede);
        $objetivo = $this->usuarioConRol(RoleSeeder::OPERARIO, $sede);

        $this->getJson('/api/usuarios')->assertStatus(403);
        $this->postJson('/api/usuarios', [])->assertStatus(403);
        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'gerente'])->assertStatus(403);
        $this->getJson('/api/registro-sistema')->assertStatus(403);
    }

    public function test_administrador_de_desguace_puede_eliminar_vehiculos_de_su_sede(): void
    {
        $sede = $this->crearSede();
        $vehiculo = $this->inventarioDe($sede)['vehiculo'];
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);

        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertOk();
    }

    public function test_administrador_de_plataforma_no_accede_al_inventario(): void
    {
        $sede = $this->crearSede();
        $vehiculo = $this->inventarioDe($sede)['vehiculo'];
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $this->getJson('/api/vehiculos')->assertStatus(403);
        $this->getJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
        $this->getJson('/api/piezas')->assertStatus(403);
        $this->postJson('/api/vehiculos', $this->datosVehiculo($sede))->assertStatus(403);
    }

    public function test_el_rol_anterior_administrador_no_conserva_permisos_ni_sede(): void
    {
        $sede = $this->crearSede();
        $this->inventarioDe($sede);
        $this->actuarComo(RoleSeeder::ADMINISTRADOR);

        $this->getJson('/api/vehiculos')->assertStatus(403);
        $this->getJson('/api/usuarios')->assertStatus(403);
        $this->getJson('/api/desguaces')->assertStatus(403);
    }

    // ---------- Gestion de roles (administrador de desguace) ----------

    public function test_administrador_de_desguace_asigna_y_cambia_roles_en_su_equipo(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);
        $objetivo = User::factory()->create(['id_desguace' => $sede->id_desguace]);

        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'operario'])->assertOk();
        $this->assertTrue($objetivo->fresh()->hasRole('operario'));

        // Cambiar el rol sustituye al anterior.
        $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => 'gerente'])->assertOk();
        $objetivo->refresh();
        $this->assertTrue($objetivo->hasRole('gerente'));
        $this->assertFalse($objetivo->hasRole('operario'));
    }

    public function test_administrador_de_desguace_retira_un_rol(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);
        $objetivo = $this->usuarioConRol('operario', $sede);

        $this->deleteJson("/api/usuarios/{$objetivo->id}/roles/operario")->assertOk();

        $this->assertFalse($objetivo->fresh()->hasRole('operario'));
    }

    public function test_no_se_pueden_conceder_roles_de_administracion(): void
    {
        $sede = $this->crearSede();
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);
        $objetivo = User::factory()->create(['id_desguace' => $sede->id_desguace]);

        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::PLATAFORMA, RoleSeeder::ADMINISTRADOR, 'superusuario'] as $rol) {
            $this->putJson("/api/usuarios/{$objetivo->id}/rol", ['rol' => $rol])->assertStatus(422);
        }

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
    }

    public function test_nadie_puede_modificar_sus_propios_roles(): void
    {
        $sede = $this->crearSede();
        $admin = $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);

        $this->putJson("/api/usuarios/{$admin->id}/rol", ['rol' => 'operario'])->assertStatus(403);
        $this->deleteJson("/api/usuarios/{$admin->id}/roles/operario")->assertStatus(403);

        $this->assertTrue($admin->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
    }

    public function test_el_registro_de_auditoria_usa_el_usuario_autenticado(): void
    {
        $sede = $this->crearSede();
        $operador = $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $sede);

        $this->postJson('/api/registro-sistema', [
            'fecha_hora' => '2026-03-01 10:00:00',
            'id_usuario' => 999999,
        ])->assertStatus(201);

        $this->assertSame($operador->id, RegistroSistema::latest('id_registro')->first()->id_usuario);
    }
}
