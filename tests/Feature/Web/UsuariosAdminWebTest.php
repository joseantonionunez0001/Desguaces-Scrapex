<?php

namespace Tests\Feature\Web;

use App\Models\Desguace;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Factories\DesguaceFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Gestión de usuarios desde la web.
 * - El administrador de desguace gestiona los gerentes y operarios de SU sede.
 * - La plataforma consulta todas las cuentas y cambia la sede por el procedimiento autorizado.
 */
class UsuariosAdminWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
    }

    private function sede(): Desguace
    {
        return Desguace::query()->withoutGlobalScopes()->findOrFail(DesguaceFactory::idPorDefecto());
    }

    /**
     * Cuenta de la sede por defecto (la misma que la del administrador de las pruebas).
     *
     * @param  array<string, mixed>  $atributos
     */
    private function cuentaDeLaSede(array $atributos = [], ?string $rol = null): User
    {
        $usuario = User::factory()->create($atributos + ['id_desguace' => $this->sede()->id_desguace]);

        if ($rol !== null) {
            $usuario->assignRole($rol);
        }

        return $usuario;
    }

    /**
     * @return array<string, string>
     */
    private function datosCuenta(array $sobrescribir = []): array
    {
        return array_merge([
            'name' => 'Gema Gerente',
            'email' => 'gema@scrapex.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
            'rol' => RoleSeeder::GERENTE,
        ], $sobrescribir);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $usuario = User::factory()->create();

        $this->get(route('admin.usuarios.index'))->assertRedirect(route('login'));
        $this->get(route('admin.usuarios.show', $usuario))->assertRedirect(route('login'));
        $this->put(route('admin.usuarios.rol.update', $usuario), ['rol' => 'gerente'])->assertRedirect(route('login'));
        $this->delete(route('admin.usuarios.rol.destroy', $usuario))->assertRedirect(route('login'));
    }

    public function test_solo_los_roles_de_administracion_acceden_a_la_gestion_de_usuarios(): void
    {
        $objetivo = $this->cuentaDeLaSede();

        foreach ([null, RoleSeeder::OPERARIO, RoleSeeder::GERENTE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol));

            $this->get(route('admin.usuarios.index'))->assertForbidden();
            $this->get(route('admin.usuarios.show', $objetivo))->assertForbidden();
            $this->get(route('admin.usuarios.create'))->assertForbidden();
            $this->post(route('admin.usuarios.store'), $this->datosCuenta())->assertForbidden();
            $this->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::OPERARIO])->assertForbidden();
            $this->delete(route('admin.usuarios.rol.destroy', $objetivo))->assertForbidden();
            $this->delete(route('admin.usuarios.destroy', $objetivo))->assertForbidden();
        }

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
        $this->assertDatabaseMissing('users', ['email' => 'gema@scrapex.test']);
    }

    // ---------- Listado y ficha (administrador de desguace) ----------

    public function test_el_administrador_ve_el_listado_de_su_sede_con_roles_y_estado(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->cuentaDeLaSede(['name' => 'Gema Gerente', 'email' => 'gema@example.com'], RoleSeeder::GERENTE);
        $this->cuentaDeLaSede(['name' => 'Pablo Pendiente', 'email' => 'pablo@example.com']);
        User::factory()->create(['name' => 'Otra Sede Olivia', 'id_desguace' => Desguace::factory()->create()->id_desguace]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Gema Gerente')
            ->assertSee('gema@example.com')
            ->assertSee('Pablo Pendiente')
            ->assertSee('Pendiente de rol')
            ->assertSee('Rol asignado')
            ->assertSee('Nueva cuenta')
            ->assertDontSee('Otra Sede Olivia')
            ->assertDontSee('password');
    }

    public function test_el_listado_se_puede_buscar_por_nombre_o_correo(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->cuentaDeLaSede(['name' => 'Lucía Torres', 'email' => 'lucia@example.com']);
        $this->cuentaDeLaSede(['name' => 'Mario Vega', 'email' => 'mario@otrodominio.es']);

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
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->cuentaDeLaSede(['name' => 'Olga Operaria'], RoleSeeder::OPERARIO);
        $this->cuentaDeLaSede(['name' => 'Sin Rol Sara']);

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
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->cuentaDeLaSede(['name' => 'Visible Víctor']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['rol' => 'superadmin', 'estado' => 'x', 'q' => ['no-texto']]))
            ->assertOk()
            ->assertSee('Visible Víctor');
    }

    public function test_el_listado_muestra_un_estado_vacio_sin_resultados(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->get(route('admin.usuarios.index', ['q' => 'no-existe-nadie-asi']))
            ->assertOk()
            ->assertSee('No se han encontrado usuarios');
    }

    public function test_el_listado_esta_paginado(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        User::factory()->count(20)->create(['id_desguace' => $this->sede()->id_desguace]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() === 21 && $usuarios->count() === 15);
    }

    public function test_la_ficha_muestra_los_datos_y_el_rol_actual(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->cuentaDeLaSede(['name' => 'Gema Gerente', 'email' => 'gema@example.com'], RoleSeeder::GERENTE);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $objetivo))
            ->assertOk()
            ->assertSee('Gema Gerente')
            ->assertSee('gema@example.com')
            ->assertSee('gerente')
            ->assertSee('Cambiar rol')
            ->assertSee('Retirar rol')
            ->assertSee('Editar cuenta')
            // El cambio de sede es tarea de la plataforma.
            ->assertDontSee('Cambiar desguace');
    }

    public function test_la_ficha_de_un_usuario_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->get('/admin/usuarios/99999')
            ->assertNotFound();
    }

    // ---------- Alta, edición y baja del equipo ----------

    public function test_el_administrador_crea_un_gerente_y_un_operario_en_su_sede(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        $this->actingAs($admin)->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('Nueva cuenta del equipo')
            ->assertSee($this->sede()->nombre)
            ->assertDontSee('value="'.RoleSeeder::ADMIN_DESGUACE.'"', false)
            ->assertDontSee('value="'.RoleSeeder::PLATAFORMA.'"', false);

        $this->actingAs($admin)->post(route('admin.usuarios.store'), $this->datosCuenta())
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.usuarios.store'), $this->datosCuenta([
            'name' => 'Oscar Operario',
            'email' => 'oscar@scrapex.test',
            'rol' => RoleSeeder::OPERARIO,
        ]))->assertSessionHas('success');

        $gema = User::where('email', 'gema@scrapex.test')->firstOrFail();
        $oscar = User::where('email', 'oscar@scrapex.test')->firstOrFail();
        $this->assertSame($this->sede()->id_desguace, $gema->id_desguace);
        $this->assertSame($this->sede()->id_desguace, $oscar->id_desguace);
        $this->assertTrue($gema->hasRole(RoleSeeder::GERENTE));
        $this->assertTrue($oscar->hasRole(RoleSeeder::OPERARIO));
    }

    public function test_el_alta_no_acepta_otra_sede_ni_roles_de_administracion(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $otraSede = Desguace::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->datosCuenta(['id_desguace' => $otraSede->id_desguace]))
            ->assertSessionHasErrors('id_desguace');

        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::PLATAFORMA, RoleSeeder::ADMINISTRADOR] as $rol) {
            $this->actingAs($admin)
                ->post(route('admin.usuarios.store'), $this->datosCuenta(['rol' => $rol]))
                ->assertSessionHasErrors('rol');
        }

        $this->assertDatabaseMissing('users', ['email' => 'gema@scrapex.test']);
    }

    public function test_el_administrador_edita_y_elimina_una_cuenta_de_su_equipo(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $operario = $this->cuentaDeLaSede(['email' => 'olga@scrapex.test'], RoleSeeder::OPERARIO);

        $this->actingAs($admin)->get(route('admin.usuarios.edit', $operario))->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $operario), $this->datosCuenta([
                'name' => 'Olga Gerente',
                'email' => 'olga@scrapex.test',
                'password' => '',
                'password_confirmation' => '',
                'rol' => RoleSeeder::GERENTE,
            ]))
            ->assertRedirect(route('admin.usuarios.show', $operario))
            ->assertSessionHas('success');

        $operario->refresh();
        $this->assertSame('Olga Gerente', $operario->name);
        $this->assertSame([RoleSeeder::GERENTE], $operario->getRoleNames()->all());
        $this->assertSame($this->sede()->id_desguace, $operario->id_desguace);

        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $operario))
            ->assertRedirect(route('admin.usuarios.index'));
        $this->assertSoftDeleted('users', ['id' => $operario->id]);
    }

    public function test_la_edicion_no_cambia_la_sede(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $operario = $this->cuentaDeLaSede(['email' => 'olga@scrapex.test'], RoleSeeder::OPERARIO);
        $otraSede = Desguace::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $operario), $this->datosCuenta([
                'email' => 'olga@scrapex.test',
                'id_desguace' => $otraSede->id_desguace,
            ]))
            ->assertSessionHasErrors('id_desguace');

        $this->assertSame($this->sede()->id_desguace, $operario->fresh()->id_desguace);
    }

    public function test_el_administrador_no_ve_ni_gestiona_cuentas_de_otra_sede(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $ajeno = $this->usuarioConRol(RoleSeeder::OPERARIO, Desguace::factory()->create());

        $this->actingAs($admin)->get(route('admin.usuarios.show', $ajeno))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.usuarios.edit', $ajeno))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.usuarios.update', $ajeno), $this->datosCuenta())->assertNotFound();
        $this->actingAs($admin)->put(route('admin.usuarios.rol.update', $ajeno), ['rol' => RoleSeeder::GERENTE])->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.usuarios.rol.destroy', $ajeno))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $ajeno))->assertNotFound();

        $ajeno->refresh();
        $this->assertSame([RoleSeeder::OPERARIO], $ajeno->getRoleNames()->all());
        $this->assertNull($ajeno->deleted_at);
    }

    public function test_el_administrador_no_gestiona_otras_cuentas_de_administracion(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $otroAdmin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        $this->actingAs($admin)->get(route('admin.usuarios.edit', $otroAdmin))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.usuarios.rol.update', $otroAdmin), ['rol' => RoleSeeder::OPERARIO])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $otroAdmin))->assertForbidden();

        $this->assertTrue($otroAdmin->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
    }

    // ---------- Roles ----------

    public function test_el_administrador_asigna_un_rol_a_un_usuario_pendiente(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->cuentaDeLaSede();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::OPERARIO])
            ->assertRedirect(route('admin.usuarios.show', $objetivo))
            ->assertSessionHas('success');

        $this->assertTrue($objetivo->fresh()->hasRole(RoleSeeder::OPERARIO));
        $this->assertTrue($objetivo->fresh()->can('vehiculos.ver'));
        $this->assertFalse($objetivo->fresh()->can('vehiculos.editar'));
    }

    public function test_cambiar_el_rol_sustituye_al_anterior(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->usuarioConRol(RoleSeeder::OPERARIO);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => RoleSeeder::GERENTE])
            ->assertRedirect();

        $this->assertSame([RoleSeeder::GERENTE], $objetivo->fresh()->getRoleNames()->all());
    }

    public function test_no_se_acepta_un_rol_inexistente_vacio_ni_de_administracion(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->cuentaDeLaSede();

        foreach (['superadmin', RoleSeeder::ADMIN_DESGUACE, RoleSeeder::PLATAFORMA, RoleSeeder::ADMINISTRADOR] as $rol) {
            $this->actingAs($admin)
                ->from(route('admin.usuarios.show', $objetivo))
                ->put(route('admin.usuarios.rol.update', $objetivo), ['rol' => $rol])
                ->assertSessionHasErrors('rol');
        }

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $objetivo), [])
            ->assertSessionHasErrors('rol');

        $this->assertCount(0, $objetivo->fresh()->getRoleNames());
    }

    public function test_el_administrador_retira_el_rol_de_un_usuario(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
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
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->cuentaDeLaSede();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.rol.destroy', $objetivo))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_un_administrador_no_puede_modificar_sus_propios_privilegios(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.rol.update', $admin), ['rol' => RoleSeeder::OPERARIO])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.rol.destroy', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
    }

    public function test_la_ficha_propia_no_ofrece_formularios_de_rol(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $admin))
            ->assertOk()
            ->assertSee('no puedes modificar tus propios privilegios')
            ->assertDontSee('Cambiar rol')
            ->assertDontSee('Retirar rol')
            ->assertDontSee('Editar cuenta');
    }

    public function test_las_acciones_de_rol_no_aceptan_get(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $objetivo = $this->cuentaDeLaSede();

        $this->actingAs($admin)
            ->get('/admin/usuarios/'.$objetivo->id.'/rol')
            ->assertStatus(405);
    }

    public function test_el_listado_no_muestra_usuarios_eliminados(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $borrado = $this->cuentaDeLaSede(['name' => 'Usuario Borrado']);
        $borrado->delete();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertDontSee('Usuario Borrado');
    }

    // ---------- Plataforma ----------

    public function test_la_plataforma_ve_todas_las_cuentas_pero_no_crea_equipos_ni_asigna_roles(): void
    {
        $plataforma = $this->usuarioConRol(RoleSeeder::PLATAFORMA);
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, Desguace::factory()->create(['nombre' => 'Desguace Uno']));
        $this->usuarioConRol(RoleSeeder::OPERARIO, Desguace::factory()->create(['nombre' => 'Desguace Dos']));

        $this->actingAs($plataforma)->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Desguace Uno')
            ->assertSee('Desguace Dos')
            ->assertDontSee('Nueva cuenta');

        $this->actingAs($plataforma)->get(route('admin.usuarios.show', $gerente))
            ->assertOk()
            ->assertSee('Cambiar desguace')
            ->assertDontSee('Cambiar rol');

        $this->actingAs($plataforma)->get(route('admin.usuarios.create'))->assertForbidden();
        $this->actingAs($plataforma)->put(route('admin.usuarios.rol.update', $gerente), ['rol' => RoleSeeder::OPERARIO])->assertForbidden();
    }

    public function test_al_cambiar_la_sede_se_revoca_el_acceso_a_la_anterior(): void
    {
        $origen = Desguace::factory()->create();
        $destino = Desguace::factory()->create();
        $vehiculoOrigen = Vehiculo::factory()->create(['id_desguace' => $origen->id_desguace]);
        $vehiculoDestino = Vehiculo::factory()->create(['id_desguace' => $destino->id_desguace]);
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, $origen);

        $this->actingAs($gerente)->get(route('vehiculos.show', $vehiculoOrigen))->assertOk();
        $this->actingAs($gerente)->get(route('vehiculos.show', $vehiculoDestino))->assertNotFound();

        $this->actingAs($this->usuarioConRol(RoleSeeder::PLATAFORMA))
            ->put(route('admin.usuarios.desguace.update', $gerente), ['id_desguace' => $destino->id_desguace])
            ->assertRedirect(route('admin.usuarios.show', $gerente))
            ->assertSessionHas('success');

        $this->assertSame($destino->id_desguace, $gerente->fresh()->id_desguace);

        $this->app['auth']->forgetGuards();
        $this->actingAs($gerente->fresh())->get(route('vehiculos.show', $vehiculoOrigen))->assertNotFound();
        $this->actingAs($gerente->fresh())->get(route('vehiculos.show', $vehiculoDestino))->assertOk();
        $this->actingAs($gerente->fresh())->get(route('vehiculos.index'))
            ->assertSee($vehiculoDestino->matricula)
            ->assertDontSee($vehiculoOrigen->matricula);
    }

    public function test_solo_la_plataforma_cambia_la_sede_y_no_la_de_los_administradores(): void
    {
        $origen = Desguace::factory()->create();
        $destino = Desguace::factory()->create();
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, $origen);
        $adminLocal = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $origen);

        // El administrador local no puede cambiar de sede a su equipo.
        $this->actingAs($adminLocal)
            ->put(route('admin.usuarios.desguace.update', $gerente), ['id_desguace' => $destino->id_desguace])
            ->assertForbidden();

        // La plataforma no mueve administradores con este procedimiento (se designan en Desguaces).
        $this->actingAs($this->usuarioConRol(RoleSeeder::PLATAFORMA))
            ->put(route('admin.usuarios.desguace.update', $adminLocal), ['id_desguace' => $destino->id_desguace])
            ->assertSessionHas('error');

        $this->assertSame($origen->id_desguace, $gerente->fresh()->id_desguace);
        $this->assertSame($origen->id_desguace, $adminLocal->fresh()->id_desguace);
    }

    public function test_una_cuenta_sin_sede_ve_el_aviso_y_queda_bloqueada(): void
    {
        $sinSede = $this->usuarioSinSede(RoleSeeder::GERENTE);

        $this->actingAs($sinSede)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Contacta con administración')
            ->assertDontSee(route('vehiculos.index'));

        $this->actingAs($sinSede)->get(route('vehiculos.index'))
            ->assertForbidden()
            ->assertSee('Contacta con administración');
    }
}
