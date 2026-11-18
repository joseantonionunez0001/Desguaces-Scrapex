<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Fila;
use App\Models\Nivel;
use App\Models\Patio;
use App\Models\Posicion;
use App\Models\Sector;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantallas web de vehículos: listado, ficha y formularios de alta, edición y baja.
 */
class VehiculosWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datosVehiculo(Desguace $desguace, array $sobrescribir = []): array
    {
        return array_merge([
            'id_desguace' => $desguace->id_desguace,
            'matricula' => '1234BCD',
            'marca' => 'SEAT',
            'modelo' => 'Ibiza',
            'fecha_llegada' => '2026-01-15',
            'numero_bastidor' => 'VSSZZZ6JZ1R000001',
            'estado_tratamiento' => 'Pendiente',
        ], $sobrescribir);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->get(route('vehiculos.index'))->assertRedirect(route('login'));
        $this->get(route('vehiculos.show', $vehiculo))->assertRedirect(route('login'));
        $this->get(route('vehiculos.create'))->assertRedirect(route('login'));
        $this->get(route('vehiculos.edit', $vehiculo))->assertRedirect(route('login'));
        $this->post(route('vehiculos.store'), [])->assertRedirect(route('login'));
        $this->delete(route('vehiculos.destroy', $vehiculo))->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403_en_todas_las_rutas(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $this->actingAs($this->usuarioConRol(null));

        $this->get(route('vehiculos.index'))->assertForbidden();
        $this->get(route('vehiculos.show', $vehiculo))->assertForbidden();
        $this->get(route('vehiculos.create'))->assertForbidden();
        $this->post(route('vehiculos.store'), [])->assertForbidden();
        $this->get(route('vehiculos.edit', $vehiculo))->assertForbidden();
        $this->put(route('vehiculos.update', $vehiculo), [])->assertForbidden();
        $this->delete(route('vehiculos.destroy', $vehiculo))->assertForbidden();
    }

    // ---------- Listado ----------

    public function test_cada_rol_autorizado_ve_el_listado_con_datos_reales(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Levante Test']);
        Vehiculo::factory()->create([
            'id_desguace' => $desguace->id_desguace,
            'matricula' => '1111BBB',
            'marca' => 'Renault',
            'modelo' => 'Clio',
            'numero_bastidor' => 'VF1ZZZ00000000001',
            'estado_tratamiento' => 'En desmontaje',
            'fecha_llegada' => '2026-02-03',
        ]);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('vehiculos.index'))
                ->assertOk()
                ->assertSeeInOrder(['1111BBB', 'Renault', 'Clio', 'VF1ZZZ00000000001', '03/02/2026', 'En desmontaje', 'Desguace Levante Test']);
        }
    }

    public function test_el_listado_vacio_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.index'))
            ->assertOk()
            ->assertSee('No hay vehículos registrados');
    }

    public function test_el_listado_ordena_por_fecha_de_llegada_descendente_y_no_muestra_borrados(): void
    {
        Vehiculo::factory()->create(['matricula' => '0001AAA', 'fecha_llegada' => '2025-01-01']);
        Vehiculo::factory()->create(['matricula' => '0002AAA', 'fecha_llegada' => '2026-03-01']);
        Vehiculo::factory()->create(['matricula' => '0003AAA', 'fecha_llegada' => '2026-01-01'])->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.index'))
            ->assertOk()
            ->assertSeeInOrder(['0002AAA', '0001AAA'])
            ->assertDontSee('0003AAA');
    }

    public function test_el_listado_pagina_de_15_en_15_y_conserva_los_filtros(): void
    {
        $desguace = Desguace::factory()->create();
        Vehiculo::factory()->count(16)->create(['id_desguace' => $desguace->id_desguace]);
        $this->actingAs($this->usuarioConRol('operario'));

        $primeraPagina = $this->get(route('vehiculos.index', ['desguace' => $desguace->id_desguace]));
        $primeraPagina->assertOk();
        $this->assertCount(15, $primeraPagina->viewData('vehiculos'));
        $primeraPagina->assertSee('desguace='.$desguace->id_desguace.'&amp;page=2', false);

        $segundaPagina = $this->get(route('vehiculos.index', ['desguace' => $desguace->id_desguace, 'page' => 2]));
        $this->assertCount(1, $segundaPagina->viewData('vehiculos'));
    }

    public function test_el_buscador_filtra_por_matricula_marca_modelo_y_bastidor(): void
    {
        Vehiculo::factory()->create(['matricula' => '9001XYZ', 'marca' => 'Citroen', 'modelo' => 'Xsara', 'numero_bastidor' => 'AAAAAAAAAAAAAAAAA']);
        Vehiculo::factory()->create(['matricula' => '9002XYZ', 'marca' => 'Opel', 'modelo' => 'Corsa', 'numero_bastidor' => 'BBBBBBBBBBBBBBBBB']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.index', ['q' => '9001']))->assertSee('9001XYZ')->assertDontSee('9002XYZ');
        $this->get(route('vehiculos.index', ['q' => 'citro']))->assertSee('9001XYZ')->assertDontSee('9002XYZ');
        $this->get(route('vehiculos.index', ['q' => 'Corsa']))->assertSee('9002XYZ')->assertDontSee('9001XYZ');
        $this->get(route('vehiculos.index', ['q' => 'BBBBBB']))->assertSee('9002XYZ')->assertDontSee('9001XYZ');
    }

    public function test_el_listado_filtra_por_desguace(): void
    {
        $norte = Desguace::factory()->create();
        $sur = Desguace::factory()->create();
        Vehiculo::factory()->create(['id_desguace' => $norte->id_desguace, 'matricula' => '7001NNN']);
        Vehiculo::factory()->create(['id_desguace' => $sur->id_desguace, 'matricula' => '7002SSS']);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.index', ['desguace' => $sur->id_desguace]))
            ->assertOk()
            ->assertSee('7002SSS')
            ->assertDontSee('7001NNN');
    }

    public function test_el_listado_filtra_por_estado_y_ofrece_los_estados_reales(): void
    {
        Vehiculo::factory()->create(['matricula' => '8001EEE', 'estado_tratamiento' => 'Chatarra certificada']);
        Vehiculo::factory()->create(['matricula' => '8002EEE', 'estado_tratamiento' => 'Pendiente']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.index'))
            ->assertSee('<option value="Chatarra certificada"', false)
            ->assertSee('<option value="Pendiente"', false);

        $this->get(route('vehiculos.index', ['estado' => 'Chatarra certificada']))
            ->assertSee('8001EEE')
            ->assertDontSee('8002EEE');
    }

    public function test_el_listado_filtra_por_rango_de_fecha_de_llegada(): void
    {
        Vehiculo::factory()->create(['matricula' => '6001FFF', 'fecha_llegada' => '2026-01-10']);
        Vehiculo::factory()->create(['matricula' => '6002FFF', 'fecha_llegada' => '2026-02-10']);
        Vehiculo::factory()->create(['matricula' => '6003FFF', 'fecha_llegada' => '2026-03-10']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.index', ['desde' => '2026-02-01', 'hasta' => '2026-02-28']))
            ->assertSee('6002FFF')
            ->assertDontSee('6001FFF')
            ->assertDontSee('6003FFF');

        $this->get(route('vehiculos.index', ['desde' => '2026-02-10']))
            ->assertSee('6002FFF')
            ->assertSee('6003FFF')
            ->assertDontSee('6001FFF');
    }

    public function test_el_listado_sin_coincidencias_ofrece_limpiar_filtros_y_los_filtros_invalidos_se_ignoran(): void
    {
        Vehiculo::factory()->create(['matricula' => '5001GGG']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.index', ['q' => 'no-existe-nada']))
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertSee('Limpiar filtros');

        $this->get(route('vehiculos.index', ['desde' => 'no-es-fecha', 'desguace' => 'abc']))
            ->assertOk()
            ->assertSee('5001GGG');
    }

    public function test_los_botones_del_listado_dependen_del_permiso(): void
    {
        $vehiculo = Vehiculo::factory()->create(['matricula' => '4001HHH']);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.index'))
            ->assertSee(route('vehiculos.show', $vehiculo, false))
            ->assertDontSee('Registrar vehículo')
            ->assertDontSee(route('vehiculos.edit', $vehiculo, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.index'))
            ->assertSee('Registrar vehículo')
            ->assertSee(route('vehiculos.edit', $vehiculo, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('vehiculos.index'))
            ->assertSee('Registrar vehículo')
            ->assertSee(route('vehiculos.edit', $vehiculo, false))
            ->assertSee('value="DELETE"', false);
    }

    // ---------- Ficha ----------

    public function test_la_ficha_muestra_los_datos_completos_el_desguace_y_las_categorias(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Central Test']);
        $vehiculo = Vehiculo::factory()->create([
            'id_desguace' => $desguace->id_desguace,
            'matricula' => '3001JJJ',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'numero_bastidor' => 'WF0ZZZ00000000001',
            'estado_tratamiento' => 'Descontaminado',
            'fecha_llegada' => '2026-04-20',
        ]);
        Categoria::factory()->create(['id_vehiculo' => $vehiculo->id_vehiculo, 'nombre' => 'Carrocería lateral']);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertOk()
            ->assertSee('3001JJJ')
            ->assertSee('Ford')
            ->assertSee('Focus')
            ->assertSee('WF0ZZZ00000000001')
            ->assertSee('Descontaminado')
            ->assertSee('20/04/2026')
            ->assertSee('Desguace Central Test')
            ->assertSee('Carrocería lateral');
    }

    public function test_la_ficha_muestra_la_ubicacion_vigente_con_su_jerarquia_y_el_enlace_al_historial(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $patio = Patio::factory()->create(['nombre' => 'Patio Norte', 'codigo' => 'PAT-01']);
        $sector = Sector::factory()->create(['id_patio' => $patio->id_patio, 'codigo' => 'S-07']);
        $fila = Fila::factory()->create(['id_sector' => $sector->id_sector, 'numero_fila' => '3']);
        $posicion = Posicion::factory()->create(['id_fila' => $fila->id_fila, 'numero_posicion' => '12']);
        $nivel = Nivel::factory()->create(['id_posicion' => $posicion->id_posicion, 'numero_nivel' => '2']);
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => null,
        ]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertOk()
            ->assertSeeInOrder(['Patio Norte (PAT-01)', 'Sector S-07', 'Fila 3', 'Posición 12', 'Nivel 2'])
            ->assertSee(route('vehiculos.ubicaciones.index', $vehiculo));
    }

    public function test_la_ficha_avisa_si_no_hay_ubicacion_vigente(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        // Una ubicación cerrada no cuenta como ubicación actual.
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-31',
        ]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertOk()
            ->assertSee('no tiene una ubicación asignada actualmente');
    }

    public function test_la_ficha_oculta_el_enlace_de_asignar_ubicacion_mientras_no_exista_la_ruta(): void
    {
        if (Route::has('vehiculos.ubicaciones.asignar')) {
            $this->markTestSkipped('La rama Antonio ya aporta la ruta de asignación: el enlace se muestra a propósito.');
        }

        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertOk()
            ->assertDontSee('Asignar ubicación');
    }

    public function test_la_ficha_muestra_asignar_ubicacion_solo_si_existe_la_ruta_y_hay_permiso(): void
    {
        // Simula la ruta que aporta otra rama del proyecto.
        Route::get('vehiculos/{vehiculo}/asignar-ubicacion', fn () => 'asignar')->name('vehiculos.ubicaciones.asignar');
        app('router')->getRoutes()->refreshNameLookups();
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertSee('Asignar ubicación')
            ->assertSee(route('vehiculos.ubicaciones.asignar', $vehiculo, false));

        // El operario puede ver el historial, pero no tiene ubicaciones_vehiculo.crear.
        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertOk()
            ->assertDontSee('Asignar ubicación');
    }

    public function test_los_botones_de_la_ficha_dependen_del_permiso(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertDontSee(route('vehiculos.edit', $vehiculo, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertSee(route('vehiculos.edit', $vehiculo, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('vehiculos.show', $vehiculo))
            ->assertSee(route('vehiculos.edit', $vehiculo, false))
            ->assertSee('value="DELETE"', false);
    }

    public function test_la_ficha_de_un_vehiculo_borrado_o_inexistente_devuelve_404(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $vehiculo->delete();
        $this->actingAs($this->usuarioConRol('administrador'));

        $this->get(route('vehiculos.show', $vehiculo->id_vehiculo))->assertNotFound();
        $this->get('/vehiculos/999999')->assertNotFound();
    }

    // ---------- Alta ----------

    public function test_el_formulario_de_alta_lista_los_desguaces_y_los_estados_existentes(): void
    {
        Desguace::factory()->create(['nombre' => 'Desguace Alta Test']);
        Vehiculo::factory()->create(['estado_tratamiento' => 'Estado ya usado']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.create'))
            ->assertOk()
            ->assertSee('Desguace Alta Test')
            ->assertSee('Estado ya usado');
    }

    public function test_un_gerente_puede_registrar_un_vehiculo(): void
    {
        $desguace = Desguace::factory()->create();

        $respuesta = $this->actingAs($this->usuarioConRol('gerente'))
            ->post(route('vehiculos.store'), $this->datosVehiculo($desguace));

        $vehiculo = Vehiculo::where('matricula', '1234BCD')->firstOrFail();
        $respuesta->assertRedirect(route('vehiculos.show', $vehiculo))
            ->assertSessionHas('flasher::envelopes');
        $this->assertDatabaseHas('vehiculos', [
            'id_desguace' => $desguace->id_desguace,
            'marca' => 'SEAT',
            'modelo' => 'Ibiza',
            'numero_bastidor' => 'VSSZZZ6JZ1R000001',
            'estado_tratamiento' => 'Pendiente',
        ]);
    }

    public function test_un_operario_no_puede_registrar_vehiculos(): void
    {
        $desguace = Desguace::factory()->create();
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.create'))->assertForbidden();
        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace))->assertForbidden();
        $this->assertDatabaseCount('vehiculos', 0);
    }

    public function test_el_alta_valida_los_campos_obligatorios(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), [])
            ->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors(['id_desguace', 'matricula', 'marca', 'modelo', 'fecha_llegada', 'numero_bastidor', 'estado_tratamiento']);

        $this->assertDatabaseCount('vehiculos', 0);
    }

    public function test_el_alta_exige_bastidor_de_17_caracteres_fecha_valida_y_desguace_existente(): void
    {
        $desguace = Desguace::factory()->create();
        $this->actingAs($this->usuarioConRol('gerente'));

        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace, ['numero_bastidor' => 'CORTO']))
            ->assertSessionHasErrors('numero_bastidor');
        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace, ['fecha_llegada' => 'ayer']))
            ->assertSessionHasErrors('fecha_llegada');
        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace, ['id_desguace' => 999999]))
            ->assertSessionHasErrors('id_desguace');
        $this->assertDatabaseCount('vehiculos', 0);
    }

    public function test_el_alta_rechaza_matricula_y_bastidor_duplicados(): void
    {
        $desguace = Desguace::factory()->create();
        Vehiculo::factory()->create(['matricula' => '1234BCD', 'numero_bastidor' => 'VSSZZZ6JZ1R000001']);
        $this->actingAs($this->usuarioConRol('gerente'));

        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace, ['numero_bastidor' => 'VSSZZZ6JZ1R000002']))
            ->assertSessionHasErrors('matricula');
        $this->post(route('vehiculos.store'), $this->datosVehiculo($desguace, ['matricula' => '9999ZZZ']))
            ->assertSessionHasErrors('numero_bastidor');
        $this->assertDatabaseCount('vehiculos', 1);
    }

    // ---------- Edición ----------

    public function test_el_formulario_de_edicion_muestra_los_datos_actuales(): void
    {
        $vehiculo = Vehiculo::factory()->create(['matricula' => '2001KKK', 'marca' => 'Peugeot', 'fecha_llegada' => '2026-06-01']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('vehiculos.edit', $vehiculo))
            ->assertOk()
            ->assertSee('value="2001KKK"', false)
            ->assertSee('value="Peugeot"', false)
            ->assertSee('value="2026-06-01"', false);
    }

    public function test_un_gerente_puede_editar_un_vehiculo_conservando_su_matricula_y_bastidor(): void
    {
        $vehiculo = Vehiculo::factory()->create(['matricula' => '1234BCD', 'numero_bastidor' => 'VSSZZZ6JZ1R000001', 'modelo' => 'Ibiza']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('vehiculos.update', $vehiculo), $this->datosVehiculo(
                Desguace::find($vehiculo->id_desguace),
                ['modelo' => 'Leon', 'estado_tratamiento' => 'Descontaminado'],
            ))
            ->assertRedirect(route('vehiculos.show', $vehiculo))
            ->assertSessionHas('flasher::envelopes');

        $this->assertDatabaseHas('vehiculos', [
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'modelo' => 'Leon',
            'estado_tratamiento' => 'Descontaminado',
        ]);
    }

    public function test_la_edicion_rechaza_la_matricula_de_otro_vehiculo(): void
    {
        Vehiculo::factory()->create(['matricula' => '5555ZZZ']);
        $vehiculo = Vehiculo::factory()->create(['matricula' => '6666ZZZ']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('vehiculos.update', $vehiculo), $this->datosVehiculo(
                Desguace::find($vehiculo->id_desguace),
                ['matricula' => '5555ZZZ', 'numero_bastidor' => $vehiculo->numero_bastidor],
            ))
            ->assertSessionHasErrors('matricula');

        $this->assertDatabaseHas('vehiculos', ['id_vehiculo' => $vehiculo->id_vehiculo, 'matricula' => '6666ZZZ']);
    }

    public function test_un_operario_no_puede_editar_vehiculos(): void
    {
        $vehiculo = Vehiculo::factory()->create(['modelo' => 'Original']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('vehiculos.edit', $vehiculo))->assertForbidden();
        $this->put(route('vehiculos.update', $vehiculo), $this->datosVehiculo(Desguace::find($vehiculo->id_desguace), ['modelo' => 'Cambiado']))
            ->assertForbidden();
        $this->assertDatabaseHas('vehiculos', ['id_vehiculo' => $vehiculo->id_vehiculo, 'modelo' => 'Original']);
    }

    // ---------- Baja ----------

    public function test_un_administrador_elimina_un_vehiculo_con_borrado_logico(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('vehiculos.destroy', $vehiculo))
            ->assertRedirect(route('vehiculos.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSoftDeleted('vehiculos', ['id_vehiculo' => $vehiculo->id_vehiculo]);
    }

    public function test_ni_el_operario_ni_el_gerente_pueden_eliminar_vehiculos(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->delete(route('vehiculos.destroy', $vehiculo))
            ->assertForbidden();
        $this->actingAs($this->usuarioConRol('gerente'))
            ->delete(route('vehiculos.destroy', $vehiculo))
            ->assertForbidden();

        $this->assertNotSoftDeleted('vehiculos', ['id_vehiculo' => $vehiculo->id_vehiculo]);
    }
}
