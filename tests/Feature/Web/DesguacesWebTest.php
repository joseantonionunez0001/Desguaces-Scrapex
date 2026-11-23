<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Patio;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantalla de desguaces.
 * - La plataforma ve todos, los crea, edita, elimina y designa a su administrador. No ve el inventario.
 * - El personal de una sede solo ve la suya (con sus contadores) y no la gestiona.
 */
class DesguacesWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    /**
     * @return array<string, string>
     */
    private function datosValidos(): array
    {
        return [
            'nombre' => 'Desguace Central',
            'direccion' => 'Calle Mayor 1',
            'numero_telefono' => '912345678',
            'ubicación' => 'Polígono Industrial',
        ];
    }

    private function plataforma(): User
    {
        return $this->usuarioConRol(RoleSeeder::PLATAFORMA);
    }

    /**
     * Define las rutas de otras ramas solo si todavía no existen en esta.
     */
    private function definirRutasDeOtrasRamas(): void
    {
        foreach ([
            'vehiculos.index' => '/vehiculos',
            'almacen.interior.index' => '/almacen/interior',
            'almacen.exterior.index' => '/almacen/exterior',
        ] as $nombre => $uri) {
            if (! Route::has($nombre)) {
                Route::get($uri, fn () => 'ok')->name($nombre);
            }
        }

        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('desguaces.index'))->assertRedirect(route('login'));
        $this->post(route('desguaces.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_recibe_403(): void
    {
        $usuario = $this->usuarioConRol();
        $desguace = Desguace::factory()->create();

        $this->actingAs($usuario)->get(route('desguaces.index'))->assertForbidden();
        $this->actingAs($usuario)->post(route('desguaces.store'), $this->datosValidos())->assertForbidden();
        $this->actingAs($usuario)->put(route('desguaces.update', $desguace), $this->datosValidos())->assertForbidden();
        $this->actingAs($usuario)->delete(route('desguaces.destroy', $desguace))->assertForbidden();
    }

    public function test_el_personal_de_la_sede_ve_su_desguace_con_ubicacion_y_contadores(): void
    {
        $desguace = Desguace::factory()->create([
            'nombre' => 'Desguace Levante',
            'direccion' => 'Avenida del Puerto 5',
            'numero_telefono' => '961234567',
            'ubicación' => 'Polígono El Pla',
        ]);
        Vehiculo::factory()->count(3)->create(['id_desguace' => $desguace->id_desguace]);
        Modulo::factory()->count(2)->create(['id_desguace' => $desguace->id_desguace]);
        Patio::factory()->create(['id_desguace' => $desguace->id_desguace]);
        Desguace::factory()->create(['nombre' => 'Desguace De Otra Sede']);

        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol, $desguace))
                ->get(route('desguaces.index'))
                ->assertOk()
                ->assertSee('Desguace Levante')
                ->assertSee('Avenida del Puerto 5')
                ->assertSee('961234567')
                ->assertSee('Polígono El Pla')
                ->assertDontSee('Desguace De Otra Sede')
                ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->total() === 1
                    && $desguaces->first()->vehiculos_count === 3
                    && $desguaces->first()->modulos_count === 2
                    && $desguaces->first()->patios_count === 1);
        }

        // Desguaces es un módulo de gestión: el operario no entra.
        $this->actingAs($this->usuarioConRol('operario', $desguace))->get(route('desguaces.index'))->assertForbidden();
    }

    public function test_la_plataforma_ve_todos_los_desguaces_sin_datos_de_inventario(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Norte']);
        Vehiculo::factory()->count(3)->create(['id_desguace' => $desguace->id_desguace]);
        Desguace::factory()->create(['nombre' => 'Desguace Sur']);

        $this->actingAs($this->plataforma())
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertSee('Desguace Norte')
            ->assertSee('Desguace Sur')
            ->assertSee('Administrador')
            ->assertDontSee('Vehículos</th>', false)
            ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->total() === 2
                && $desguaces->first()->vehiculos_count === null);
    }

    public function test_estado_vacio(): void
    {
        $this->actingAs($this->plataforma())
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertSee('Todavía no hay desguaces')
            ->assertSee('Nuevo desguace');
    }

    public function test_buscador_por_nombre_direccion_telefono_y_ubicacion(): void
    {
        Desguace::factory()->create(['nombre' => 'Desguace Norte', 'direccion' => 'Calle A', 'numero_telefono' => '911111111', 'ubicación' => 'Zona Franca']);
        Desguace::factory()->create(['nombre' => 'Desguace Sur', 'direccion' => 'Calle B', 'numero_telefono' => '922222222', 'ubicación' => 'Polígono Sur']);
        $usuario = $this->plataforma();

        foreach (['Norte', 'Calle A', '911111111', 'Zona Franca'] as $busqueda) {
            $this->actingAs($usuario)->get(route('desguaces.index', ['q' => $busqueda]))
                ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->total() === 1 && $desguaces->first()->nombre === 'Desguace Norte');
        }

        $this->actingAs($usuario)->get(route('desguaces.index', ['q' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_el_listado_se_pagina(): void
    {
        Desguace::factory()->count(16)->create();

        $this->actingAs($this->plataforma())
            ->get(route('desguaces.index', ['page' => 2]))
            ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->count() === 1 && $desguaces->total() === 16);
    }

    public function test_los_accesos_a_vehiculos_y_almacenes_apuntan_al_desguace(): void
    {
        $this->definirRutasDeOtrasRamas();
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente', $desguace))
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertSee(route('vehiculos.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee(route('almacen.interior.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee(route('almacen.exterior.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee('Almacén interior')
            ->assertSee('Almacén exterior');
    }

    public function test_solo_la_plataforma_ve_las_acciones_de_gestion(): void
    {
        $desguace = Desguace::factory()->create();
        $botonDeEdicion = 'data-modal-open="editar-desguace-'.$desguace->id_desguace.'"';
        $botonDeAdministrador = 'data-modal-open="administrador-desguace-'.$desguace->id_desguace.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol, $desguace))->get(route('desguaces.index'))
                ->assertOk()
                ->assertDontSee('Nuevo desguace')
                ->assertDontSee($botonDeEdicion, false)
                ->assertDontSee($botonDeAdministrador, false)
                ->assertDontSee($formularioDeBorrado, false);
        }

        $this->actingAs($this->plataforma())->get(route('desguaces.index'))
            ->assertSee('Nuevo desguace')
            ->assertSee($botonDeEdicion, false)
            ->assertSee($botonDeAdministrador, false)
            ->assertSee($formularioDeBorrado, false);
    }

    public function test_el_personal_de_una_sede_no_puede_crear_editar_ni_eliminar(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Original']);

        foreach (['operario', 'gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $usuario = $this->usuarioConRol($rol, $desguace);

            $this->actingAs($usuario)->post(route('desguaces.store'), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->put(route('desguaces.update', $desguace), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->delete(route('desguaces.destroy', $desguace))->assertForbidden();
            $this->actingAs($usuario)->put(route('desguaces.administrador.update', $desguace), ['email' => 'x@scrapex.test'])->assertForbidden();
        }

        $this->assertDatabaseCount('desguaces', 1);
        $this->assertSame('Original', $desguace->fresh()->nombre);
        $this->assertNotSoftDeleted($desguace);
    }

    public function test_la_plataforma_crea_un_desguace_con_ubicacion_con_tilde(): void
    {
        $this->actingAs($this->plataforma())
            ->post(route('desguaces.store'), $this->datosValidos())
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('desguaces', [
            'nombre' => 'Desguace Central',
            'direccion' => 'Calle Mayor 1',
            'numero_telefono' => '912345678',
            'ubicación' => 'Polígono Industrial',
        ]);
    }

    public function test_la_plataforma_edita_un_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->plataforma())
            ->put(route('desguaces.update', $desguace), ['nombre' => 'Renombrado'] + $this->datosValidos())
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('success');

        $this->assertSame('Renombrado', $desguace->fresh()->nombre);
        $this->assertSame('Polígono Industrial', $desguace->fresh()->{'ubicación'});
    }

    public function test_validacion_de_campos_obligatorios_y_longitudes(): void
    {
        $plataforma = $this->plataforma();

        $this->actingAs($plataforma)
            ->from(route('desguaces.index'))
            ->post(route('desguaces.store'), ['_modal' => 'crear-desguace'])
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHasErrors(['nombre', 'direccion', 'numero_telefono', 'ubicación'])
            ->assertSessionHasInput('_modal', 'crear-desguace');

        $this->actingAs($plataforma)
            ->post(route('desguaces.store'), [
                'nombre' => str_repeat('a', 256),
                'direccion' => str_repeat('a', 256),
                'numero_telefono' => str_repeat('1', 21),
                'ubicación' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors(['nombre', 'direccion', 'numero_telefono', 'ubicación']);

        $this->assertDatabaseCount('desguaces', 0);
    }

    public function test_la_plataforma_elimina_un_desguace_sin_datos_asociados(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->plataforma())
            ->delete(route('desguaces.destroy', $desguace))
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($desguace);
    }

    /**
     * La plataforma no ve el inventario, pero el borrado cuenta los datos reales de la sede.
     */
    public function test_no_se_elimina_un_desguace_con_datos_asociados(): void
    {
        $conVehiculos = Desguace::factory()->create();
        Vehiculo::factory()->create(['id_desguace' => $conVehiculos->id_desguace]);
        $conCategorias = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $conCategorias->id_desguace]);
        Categoria::factory()->create(['id_desguace' => $conCategorias->id_desguace, 'id_modulo' => $modulo->id_modulo]);
        $plataforma = $this->plataforma();

        foreach ([$conVehiculos, $conCategorias] as $desguace) {
            $this->actingAs($plataforma)
                ->delete(route('desguaces.destroy', $desguace))
                ->assertRedirect(route('desguaces.index'))
                ->assertSessionHas('error');

            $this->assertNotSoftDeleted($desguace);
        }
    }

    // ---------- Administrador de cada sede ----------

    public function test_la_plataforma_da_de_alta_al_administrador_de_dos_desguaces(): void
    {
        $alfa = Desguace::factory()->create(['nombre' => 'Desguace Alfa']);
        $beta = Desguace::factory()->create(['nombre' => 'Desguace Beta']);
        $existente = User::factory()->create(['email' => 'beta@scrapex.test', 'id_desguace' => null]);
        $plataforma = $this->plataforma();

        // Alfa: cuenta nueva.
        $this->actingAs($plataforma)
            ->put(route('desguaces.administrador.update', $alfa), [
                'email' => 'alfa@scrapex.test',
                'name' => 'Ana Alfa',
                'password' => 'clave-segura-1',
            ])
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('success');

        // Beta: cuenta existente.
        $this->actingAs($plataforma)
            ->put(route('desguaces.administrador.update', $beta), ['email' => 'beta@scrapex.test'])
            ->assertRedirect(route('desguaces.index'));

        $ana = User::where('email', 'alfa@scrapex.test')->firstOrFail();
        $this->assertTrue($ana->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertSame($alfa->id_desguace, $ana->id_desguace);
        $this->assertTrue($existente->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertSame($beta->id_desguace, $existente->fresh()->id_desguace);

        $this->actingAs($plataforma)->get(route('desguaces.index'))
            ->assertSee('alfa@scrapex.test')
            ->assertSee('beta@scrapex.test');
    }

    public function test_la_plataforma_sustituye_al_administrador_y_el_anterior_pierde_el_acceso(): void
    {
        $sede = Desguace::factory()->create();
        $anterior = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $sede);
        $nuevo = User::factory()->create(['email' => 'nuevo@scrapex.test', 'id_desguace' => null]);

        $this->actingAs($this->plataforma())
            ->put(route('desguaces.administrador.update', $sede), ['email' => 'nuevo@scrapex.test'])
            ->assertSessionHas('success');

        $this->assertFalse($anterior->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertTrue($nuevo->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));

        $this->app['auth']->forgetGuards();
        $this->actingAs($anterior->fresh())->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($anterior->fresh())->get(route('desguaces.index'))->assertForbidden();
    }

    public function test_una_cuenta_nueva_exige_nombre_y_contrasena(): void
    {
        $sede = Desguace::factory()->create();

        $this->actingAs($this->plataforma())
            ->put(route('desguaces.administrador.update', $sede), ['email' => 'nadie@scrapex.test'])
            ->assertSessionHasErrors(['name', 'password']);

        $this->assertDatabaseMissing('users', ['email' => 'nadie@scrapex.test']);
    }

    public function test_no_se_designa_a_una_cuenta_de_plataforma_ni_al_administrador_de_otra_sede(): void
    {
        $alfa = Desguace::factory()->create();
        $beta = Desguace::factory()->create();
        $adminBeta = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $beta);
        $otraPlataforma = $this->plataforma();

        $this->actingAs($this->plataforma())
            ->put(route('desguaces.administrador.update', $alfa), ['email' => $adminBeta->email])
            ->assertSessionHasErrors('email');
        $this->actingAs($this->plataforma())
            ->put(route('desguaces.administrador.update', $alfa), ['email' => $otraPlataforma->email])
            ->assertSessionHasErrors('email');

        $this->assertSame($beta->id_desguace, $adminBeta->fresh()->id_desguace);
        $this->assertFalse($otraPlataforma->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
    }
}
