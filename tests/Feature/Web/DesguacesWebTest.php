<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Patio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

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

    public function test_todos_los_roles_ven_los_desguaces_con_ubicacion_y_contadores(): void
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

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('desguaces.index'))
                ->assertOk()
                ->assertSee('Desguace Levante')
                ->assertSee('Avenida del Puerto 5')
                ->assertSee('961234567')
                ->assertSee('Polígono El Pla')
                ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->first()->vehiculos_count === 3
                    && $desguaces->first()->modulos_count === 2
                    && $desguaces->first()->patios_count === 1);
        }
    }

    public function test_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertSee('Todavía no hay desguaces')
            ->assertDontSee('Nuevo desguace');
    }

    public function test_buscador_por_nombre_direccion_telefono_y_ubicacion(): void
    {
        Desguace::factory()->create(['nombre' => 'Desguace Norte', 'direccion' => 'Calle A', 'numero_telefono' => '911111111', 'ubicación' => 'Zona Franca']);
        Desguace::factory()->create(['nombre' => 'Desguace Sur', 'direccion' => 'Calle B', 'numero_telefono' => '922222222', 'ubicación' => 'Polígono Sur']);
        $usuario = $this->usuarioConRol('operario');

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

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('desguaces.index', ['page' => 2]))
            ->assertViewHas('desguaces', fn ($desguaces) => $desguaces->count() === 1 && $desguaces->total() === 16);
    }

    public function test_los_accesos_a_otras_ramas_no_se_muestran_si_la_ruta_no_existe(): void
    {
        if (Route::has('vehiculos.index') || Route::has('almacen.interior.index') || Route::has('almacen.exterior.index')) {
            $this->markTestSkipped('Las rutas de otras ramas ya existen en esta rama integrada.');
        }

        Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertDontSee('Almacén interior')
            ->assertDontSee('Almacén exterior');
    }

    public function test_los_accesos_a_vehiculos_y_almacenes_apuntan_al_desguace(): void
    {
        $this->definirRutasDeOtrasRamas();
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('desguaces.index'))
            ->assertOk()
            ->assertSee(route('vehiculos.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee(route('almacen.interior.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee(route('almacen.exterior.index', ['desguace' => $desguace->id_desguace]))
            ->assertSee('Almacén interior')
            ->assertSee('Almacén exterior');
    }

    public function test_solo_el_administrador_ve_las_acciones_de_gestion(): void
    {
        $desguace = Desguace::factory()->create();
        $botonDeEdicion = 'data-modal-open="editar-desguace-'.$desguace->id_desguace.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        foreach (['operario', 'gerente'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get(route('desguaces.index'))
                ->assertOk()
                ->assertDontSee('Nuevo desguace')
                ->assertDontSee($botonDeEdicion, false)
                ->assertDontSee($formularioDeBorrado, false);
        }

        $this->actingAs($this->usuarioConRol('administrador'))->get(route('desguaces.index'))
            ->assertSee('Nuevo desguace')
            ->assertSee($botonDeEdicion, false)
            ->assertSee($formularioDeBorrado, false);
    }

    public function test_operario_y_gerente_no_pueden_crear_editar_ni_eliminar(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Original']);

        foreach (['operario', 'gerente'] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            $this->actingAs($usuario)->post(route('desguaces.store'), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->put(route('desguaces.update', $desguace), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->delete(route('desguaces.destroy', $desguace))->assertForbidden();
        }

        $this->assertDatabaseCount('desguaces', 1);
        $this->assertSame('Original', $desguace->fresh()->nombre);
        $this->assertNotSoftDeleted($desguace);
    }

    public function test_el_administrador_crea_un_desguace_con_ubicacion_con_tilde(): void
    {
        $this->actingAs($this->usuarioConRol('administrador'))
            ->post(route('desguaces.store'), $this->datosValidos())
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertDatabaseHas('desguaces', [
            'nombre' => 'Desguace Central',
            'direccion' => 'Calle Mayor 1',
            'numero_telefono' => '912345678',
            'ubicación' => 'Polígono Industrial',
        ]);
    }

    public function test_el_administrador_edita_un_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->put(route('desguaces.update', $desguace), ['nombre' => 'Renombrado'] + $this->datosValidos())
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSame('Renombrado', $desguace->fresh()->nombre);
        $this->assertSame('Polígono Industrial', $desguace->fresh()->{'ubicación'});
    }

    public function test_validacion_de_campos_obligatorios_y_longitudes(): void
    {
        $administrador = $this->usuarioConRol('administrador');

        $this->actingAs($administrador)
            ->from(route('desguaces.index'))
            ->post(route('desguaces.store'), ['_modal' => 'crear-desguace'])
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHasErrors(['nombre', 'direccion', 'numero_telefono', 'ubicación'])
            ->assertSessionHasInput('_modal', 'crear-desguace');

        $this->actingAs($administrador)
            ->post(route('desguaces.store'), [
                'nombre' => str_repeat('a', 256),
                'direccion' => str_repeat('a', 256),
                'numero_telefono' => str_repeat('1', 21),
                'ubicación' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors(['nombre', 'direccion', 'numero_telefono', 'ubicación']);

        $this->assertDatabaseCount('desguaces', 0);
    }

    public function test_el_administrador_elimina_un_desguace_sin_datos_asociados(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('desguaces.destroy', $desguace))
            ->assertRedirect(route('desguaces.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSoftDeleted($desguace);
    }

    public function test_no_se_elimina_un_desguace_con_datos_asociados(): void
    {
        $conVehiculos = Desguace::factory()->create();
        Vehiculo::factory()->create(['id_desguace' => $conVehiculos->id_desguace]);
        $conCategorias = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $conCategorias->id_desguace]);
        Categoria::factory()->create(['id_desguace' => $conCategorias->id_desguace, 'id_modulo' => $modulo->id_modulo]);
        $administrador = $this->usuarioConRol('administrador');

        foreach ([$conVehiculos, $conCategorias] as $desguace) {
            $this->actingAs($administrador)
                ->delete(route('desguaces.destroy', $desguace))
                ->assertRedirect(route('desguaces.index'))
                ->assertSessionHas('flasher::envelopes');

            $this->assertNotSoftDeleted($desguace);
        }
    }
}
