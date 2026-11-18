<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class UsuariosAdminWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $usuario = User::factory()->create();

        $this->get(route('admin.usuarios.index'))->assertRedirect(route('login'));
        $this->get(route('admin.usuarios.show', $usuario))->assertRedirect(route('login'));
        $this->put(route('admin.usuarios.rol.update', $usuario), ['rol' => 'gerente'])->assertRedirect(route('login'));
        $this->delete(route('admin.usuarios.rol.destroy', $usuario))->assertRedirect(route('login'));
    }

    public function test_solo_el_administrador_accede_a_la_gestion_de_usuarios(): void
    {
        $objetivo = User::factory()->create();

        foreach ([null, RoleSeeder::OPERARIO, RoleSeeder::GERENTE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol));

            $this->get(route('admin.usuarios.index'))->assertForbidden();
            $this->get(route('admin.usuarios.show', $objetivo))->assertForbidden();
            $this->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::ADMINISTRADOR])->assertForbidden();
            $this->delete(route('admin.usuarios.rol.destroy', $objetivo))->assertForbidden();
        }

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
    }

    public function test_el_administrador_ve_el_listado_con_roles_y_estado(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $gerente = User::factory()->create(['name' => 'Gema Gerente', 'email' => 'gema@example.com']);
        $gerente->assignRole(RoleSeeder::GERENTE);
        User::factory()->create(['name' => 'Pablo Pendiente', 'email' => 'pablo@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Gema Gerente')
            ->assertSee('gema@example.com')
            ->assertSee('Pablo Pendiente')
            ->assertSee('Pendiente de rol')
            ->assertSee('Rol asignado')
            ->assertDontSee('password');
    }

    public function test_el_listado_se_puede_buscar_por_nombre_o_correo(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        User::factory()->create(['name' => 'Lucía Torres', 'email' => 'lucia@example.com']);
        User::factory()->create(['name' => 'Mario Vega', 'email' => 'mario@otrodominio.es']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['q' => 'Lucía']))
            ->assertOk()
            ->assertSee('Lucía Torres')
            ->assertDontSee('Mario Vega');

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['q' => 'otrodominio']))
            ->assertOk()
            ->assertSee('Mario Vega')
            ->assertDontSee('Lucía Torres');
    }

    public function test_el_listado_se_filtra_por_rol_y_por_estado(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $operario = User::factory()->create(['name' => 'Olga Operaria']);
        $operario->assignRole(RoleSeeder::OPERARIO);
        User::factory()->create(['name' => 'Sin Rol Sara']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['rol' => RoleSeeder::OPERARIO]))
            ->assertSee('Olga Operaria')
            ->assertDontSee('Sin Rol Sara');

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['estado' => 'sin_rol']))
            ->assertSee('Sin Rol Sara')
            ->assertDontSee('Olga Operaria');
    }

    public function test_un_filtro_de_rol_no_valido_se_ignora(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        User::factory()->create(['name' => 'Visible Víctor']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['rol' => 'superadmin', 'estado' => 'x', 'q' => ['no-texto']]))
            ->assertOk()
            ->assertSee('Visible Víctor');
    }

    public function test_el_listado_muestra_un_estado_vacio_sin_resultados(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['q' => 'no-existe-nadie-asi']))
            ->assertOk()
            ->assertSee('No se han encontrado usuarios');
    }

    public function test_el_listado_esta_paginado(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        User::factory()->count(20)->create();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() === 21 && $usuarios->count() === 15);
    }

    public function test_la_ficha_muestra_los_datos_y_el_rol_actual(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = User::factory()->create(['name' => 'Gema Gerente', 'email' => 'gema@example.com']);
        $objetivo->assignRole(RoleSeeder::GERENTE);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $objetivo))
            ->assertOk()
            ->assertSee('Gema Gerente')
            ->assertSee('gema@example.com')
            ->assertSee('gerente')
            ->assertSee('Cambiar rol')
            ->assertSee('Retirar rol');
    }

    public function test_la_ficha_de_un_usuario_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMINISTRADOR))
            ->get('/admin/usuarios/99999')
            ->assertNotFound();
    }

    public function test_el_administrador_asigna_un_rol_a_un_usuario_pendiente(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::OPERARIO])
            ->assertRedirect(route('admin.usuarios.show', $objetivo))
            ->assertSessionHas('success');

        $this->assertTrue($objetivo->fresh()->hasRole(RoleSeeder::OPERARIO));
        $this->assertTrue($objetivo->fresh()->can('movimientos.crear'));
    }

    public function test_cambiar_el_rol_sustituye_al_anterior(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = $this->usuarioConRol(RoleSeeder::OPERARIO);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::GERENTE])
            ->assertRedirect();

        $this->assertSame([RoleSeeder::GERENTE], $objetivo->fresh()->getRoleNames()->all());
    }

    public function test_no_se_acepta_un_rol_inexistente_ni_vacio(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.usuarios.show', $objetivo))
            ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => 'superadmin'])
            ->assertSessionHasErrors('rol');

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), [])
            ->assertSessionHasErrors('rol');

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
    }

    public function test_el_administrador_retira_el_rol_de_un_usuario(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = $this->usuarioConRol(RoleSeeder::GERENTE);

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.rol.destroy', $objetivo))
            ->assertRedirect(route('admin.usuarios.show', $objetivo))
            ->assertSessionHas('success');

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
        $this->assertFalse($objetivo->fresh()->can('vehiculos.ver'));
    }

    public function test_retirar_el_rol_a_quien_no_tiene_ninguno_avisa_sin_error(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.rol.destroy', $objetivo))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_un_administrador_no_puede_modificar_sus_propios_privilegios(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $otro = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $admin), ['rol' => RoleSeeder::OPERARIO])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.rol.destroy', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasRole(RoleSeeder::ADMINISTRADOR));
        $this->assertTrue($otro->fresh()->hasRole(RoleSeeder::ADMINISTRADOR));
    }

    public function test_la_ficha_propia_no_ofrece_formularios_de_rol(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $admin))
            ->assertOk()
            ->assertSee('no puedes modificar tus propios privilegios')
            ->assertDontSee('Cambiar rol')
            ->assertDontSee('Retirar rol');
    }

    public function test_no_se_puede_degradar_ni_retirar_al_ultimo_administrador(): void
    {
        // El actor tiene el permiso sin ser administrador: así el objetivo es el único administrador.
        $actor = User::factory()->create();
        $actor->givePermissionTo('usuarios.asignar_roles');
        $unicoAdmin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);

        $this->actingAs($actor)
            ->put(route('admin.usuarios.rol.update', $unicoAdmin), ['rol' => RoleSeeder::GERENTE])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->delete(route('admin.usuarios.rol.destroy', $unicoAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($unicoAdmin->fresh()->hasRole(RoleSeeder::ADMINISTRADOR));
    }

    public function test_las_acciones_de_rol_no_aceptan_get(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin/usuarios/'.$objetivo->id.'/rol')
            ->assertStatus(405);
    }

    public function test_el_listado_no_filtra_a_usuarios_eliminados(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMINISTRADOR);
        $borrado = User::factory()->create(['name' => 'Usuario Borrado']);
        $borrado->delete();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertDontSee('Usuario Borrado');
    }
}
