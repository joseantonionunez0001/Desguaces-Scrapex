<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private function url(): string
    {
        return route('admin.roles-permisos.index');
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->prepararRoles();

        $this->get($this->url())->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $this->prepararRoles();

        $this->actingAs($this->usuarioConRol(null))->get($this->url())->assertForbidden();
    }

    public function test_gerente_y_operario_reciben_403(): void
    {
        $this->prepararRoles();

        foreach (['gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get($this->url())->assertForbidden();
        }
    }

    public function test_el_administrador_ve_la_matriz(): void
    {
        $this->prepararRoles();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get($this->url())
            ->assertOk()
            ->assertSee('Roles y permisos')
            ->assertSee('Administrador')
            ->assertSee('Gerente')
            ->assertSee('Operario')
            ->assertSee('Vehículos')
            ->assertSee('Ubicaciones de vehículos')
            ->assertSee('usuarios.asignar_roles')
            ->assertSee('registro_sistema.ver');
    }

    public function test_avisa_de_que_es_de_solo_lectura_y_cual_es_la_referencia(): void
    {
        $this->prepararRoles();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get($this->url())
            ->assertSee('solo de consulta')
            ->assertSee('RolePermissionSeeder');
    }

    // ---------- Contenido ----------

    public function test_la_matriz_refleja_los_permisos_reales_de_cada_rol(): void
    {
        $this->prepararRoles();

        $respuesta = $this->actingAs($this->usuarioConRol('administrador'))->get($this->url())->assertOk();

        // Administrador: todo. Gerente: crea y edita vehículos, pero no elimina. Operario: solo consulta (y crea movimientos).
        $respuesta
            ->assertSee('Permitido: Eliminar vehículos (administrador)')
            ->assertSee('Permitido: Crear vehículos (gerente)')
            ->assertSee('No permitido: Eliminar vehículos (gerente)')
            ->assertSee('Permitido: Ver vehículos (operario)')
            ->assertSee('No permitido: Crear vehículos (operario)')
            ->assertSee('Permitido: Crear movimientos (operario)')
            ->assertSee('No permitido: Editar movimientos (operario)')
            ->assertSee('Permitido: usuarios.asignar_roles (administrador)')
            ->assertSee('No permitido: usuarios.asignar_roles (gerente)')
            ->assertSee('No permitido: registro_sistema.ver (operario)');
    }

    public function test_un_cambio_de_permisos_en_la_base_de_datos_se_refleja_en_la_pantalla(): void
    {
        $this->prepararRoles();
        Role::findByName('gerente', 'web')->revokePermissionTo('vehiculos.crear');

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get($this->url())
            ->assertSee('No permitido: Crear vehículos (gerente)')
            ->assertDontSee('Permitido: Crear vehículos (gerente)');
    }

    public function test_muestra_el_numero_de_usuarios_de_cada_rol_sin_contar_los_eliminados(): void
    {
        $this->prepararRoles();
        $administrador = $this->usuarioConRol('administrador');
        $this->usuarioConRol('gerente');
        $this->usuarioConRol('gerente');
        $this->usuarioConRol('gerente')->delete();

        $this->actingAs($administrador)
            ->get($this->url())
            ->assertSeeInOrder(['Administrador', '1 usuario', 'Gerente', '2 usuarios', 'Operario', '0 usuarios']);
    }

    public function test_sin_roles_en_la_base_de_datos_muestra_el_estado_vacio(): void
    {
        // Solo existe el permiso de acceso: los roles todavía no se han sembrado.
        $permiso = Permission::create(['name' => 'usuarios.asignar_roles', 'guard_name' => 'web']);
        $usuario = $this->usuarioConRol(null);
        $usuario->givePermissionTo($permiso);

        $this->actingAs($usuario)
            ->get($this->url())
            ->assertOk()
            ->assertSee('Todavía no hay roles en la base de datos')
            ->assertDontSee('Permisos por recurso');
    }

    // ---------- Solo lectura ----------

    public function test_la_pantalla_no_tiene_rutas_de_modificacion(): void
    {
        $this->prepararRoles();
        $administrador = $this->usuarioConRol('administrador');

        $this->actingAs($administrador)->post($this->url(), [])->assertStatus(405);
        $this->actingAs($administrador)->put($this->url(), [])->assertStatus(405);
        $this->actingAs($administrador)->delete($this->url())->assertStatus(405);
    }
}
