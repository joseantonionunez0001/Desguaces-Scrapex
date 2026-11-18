<?php

namespace Tests\Feature\Web;

use App\Models\Desguace;
use App\Models\Fila;
use App\Models\Nivel;
use App\Models\Patio;
use App\Models\Posicion;
use App\Models\Sector;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AlmacenExteriorTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function urlIndice(array $consulta = []): string
    {
        return route('almacen.exterior.index', $consulta);
    }

    /**
     * Crea la jerarquía física completa hasta la posición y devuelve sus elementos.
     *
     * @return array{desguace: Desguace, patio: Patio, sector: Sector, fila: Fila, posicion: Posicion}
     */
    private function crearPosicion(int $capacidad = 3): array
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Exterior']);
        $patio = Patio::factory()->create(['id_desguace' => $desguace->id_desguace, 'codigo' => 'PAT-01', 'nombre' => 'Patio Principal']);
        $sector = Sector::factory()->create(['id_patio' => $patio->id_patio, 'codigo' => 'S-01', 'tipo_zona' => 'Motores y cajas']);
        $fila = Fila::factory()->create(['id_sector' => $sector->id_sector, 'numero_fila' => '4']);
        $posicion = Posicion::factory()->create(['id_fila' => $fila->id_fila, 'numero_posicion' => '9', 'capacidad_maxima' => $capacidad]);

        return compact('desguace', 'patio', 'sector', 'fila', 'posicion');
    }

    private function urlDePosicion(array $e): string
    {
        return $this->urlIndice([
            'desguace' => $e['desguace']->id_desguace,
            'patio' => $e['patio']->id_patio,
            'sector' => $e['sector']->id_sector,
            'fila' => $e['fila']->id_fila,
        ]);
    }

    private function urlDeNiveles(array $e): string
    {
        return $this->urlDePosicion($e).'&posicion='.$e['posicion']->id_posicion;
    }

    private function ubicar(Nivel $nivel, ?string $fin = null, ?Vehiculo $vehiculo = null): UbicacionVehiculo
    {
        return UbicacionVehiculo::factory()->create([
            'id_vehiculo' => ($vehiculo ?? Vehiculo::factory()->create())->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => $fin,
        ]);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get($this->urlIndice())->assertRedirect(route('login'));
        $this->post(route('almacen.exterior.patios.store'), [])->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $this->actingAs($this->usuarioConRol(null))->get($this->urlIndice())->assertForbidden();
    }

    public function test_cada_rol_autorizado_ve_la_pantalla(): void
    {
        Desguace::factory()->create(['nombre' => 'Desguace Levante']);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get($this->urlIndice())
                ->assertOk()
                ->assertSee('Almacén exterior')
                ->assertSee('Desguace Levante');
        }
    }

    public function test_sin_desguaces_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('operario'))
            ->get($this->urlIndice())
            ->assertOk()
            ->assertSee('No hay desguaces');
    }

    // ---------- Navegación ----------

    public function test_se_navega_por_patios_sectores_filas_y_posiciones(): void
    {
        $e = $this->crearPosicion();
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => $e['desguace']->id_desguace]))
            ->assertOk()->assertSee('Patio Principal')->assertSee('PAT-01');

        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio]))
            ->assertOk()->assertSee('Motores y cajas');

        $this->actingAs($usuario)->get($this->urlIndice([
            'desguace' => $e['desguace']->id_desguace,
            'patio' => $e['patio']->id_patio,
            'sector' => $e['sector']->id_sector,
        ]))->assertOk()->assertSee('Fila 4');

        $this->actingAs($usuario)->get($this->urlDePosicion($e))
            ->assertOk()->assertSee('Posición 9');
    }

    public function test_las_urls_con_un_camino_incorrecto_devuelven_404(): void
    {
        $e = $this->crearPosicion();
        $sectorAjeno = Sector::factory()->create();
        $usuario = $this->usuarioConRol('administrador');

        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => $e['desguace']->id_desguace, 'patio' => 99999]))->assertNotFound();
        $this->actingAs($usuario)->get($this->urlIndice([
            'desguace' => $e['desguace']->id_desguace,
            'patio' => $e['patio']->id_patio,
            'sector' => $sectorAjeno->id_sector,
        ]))->assertNotFound();
        $this->actingAs($usuario)->get($this->urlIndice(['patio' => $e['patio']->id_patio]))->assertNotFound();
    }

    // ---------- Ocupación ----------

    public function test_la_posicion_muestra_ocupados_sobre_capacidad_contando_solo_ubicaciones_vigentes(): void
    {
        $e = $this->crearPosicion(capacidad: 4);
        $niveles = Nivel::factory()->count(4)->create(['id_posicion' => $e['posicion']->id_posicion]);

        $this->ubicar($niveles[0]);                       // vigente
        $this->ubicar($niveles[1]);                       // vigente
        $this->ubicar($niveles[2], fin: '2026-02-01');    // cerrada: no cuenta
        $eliminada = $this->ubicar($niveles[3]);          // eliminada lógicamente: no cuenta
        $eliminada->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get($this->urlDePosicion($e))
            ->assertOk()
            ->assertSee('2 / 4', false);
    }

    public function test_una_posicion_sin_vehiculos_muestra_cero_ocupados(): void
    {
        $e = $this->crearPosicion(capacidad: 2);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get($this->urlDePosicion($e))
            ->assertSee('0 / 2', false);
    }

    public function test_el_nivel_se_muestra_ocupado_solo_con_ubicacion_vigente(): void
    {
        $e = $this->crearPosicion();
        $nivelOcupado = Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion, 'numero_nivel' => '1']);
        Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion, 'numero_nivel' => '2']);
        $nivelCerrado = Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion, 'numero_nivel' => '3']);
        $vehiculo = Vehiculo::factory()->create(['matricula' => '1234BCD']);
        $this->ubicar($nivelOcupado, vehiculo: $vehiculo);
        $this->ubicar($nivelCerrado, fin: '2026-03-01');

        $respuesta = $this->actingAs($this->usuarioConRol('operario'))
            ->get($this->urlDeNiveles($e))
            ->assertOk()
            ->assertSee('Niveles de la posición')
            ->assertSee('Ocupado')
            ->assertSee('1234BCD')
            ->assertSee('Libre');

        // Un nivel ocupado; el libre y el que solo tiene una ubicación cerrada figuran como libres.
        $contenido = $respuesta->getContent();
        $this->assertSame(1, substr_count($contenido, '>Ocupado<'));
        $this->assertSame(2, substr_count($contenido, '>Libre<'));
    }

    // ---------- Alta ----------

    public function test_el_gerente_crea_un_patio_un_sector_una_fila_una_posicion_y_un_nivel(): void
    {
        $desguace = Desguace::factory()->create();
        $usuario = $this->usuarioConRol('gerente');

        $this->actingAs($usuario)->post(route('almacen.exterior.patios.store'), [
            'id_desguace' => $desguace->id_desguace,
            'codigo' => 'PAT-NEW',
            'nombre' => 'Patio nuevo',
            'ruta' => ['desguace' => $desguace->id_desguace],
        ])->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace]));
        $patio = Patio::where('codigo', 'PAT-NEW')->firstOrFail();

        $this->actingAs($usuario)->post(route('almacen.exterior.sectores.store'), [
            'id_patio' => $patio->id_patio,
            'codigo' => 'S-NEW',
            'tipo_zona' => 'Chatarra',
            'ruta' => ['desguace' => $desguace->id_desguace, 'patio' => $patio->id_patio],
        ])->assertSessionHasNoErrors();
        $sector = Sector::where('codigo', 'S-NEW')->firstOrFail();

        $this->actingAs($usuario)->post(route('almacen.exterior.filas.store'), [
            'id_sector' => $sector->id_sector,
            'numero_fila' => '1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'patio' => $patio->id_patio, 'sector' => $sector->id_sector],
        ])->assertSessionHasNoErrors();
        $fila = Fila::where('id_sector', $sector->id_sector)->firstOrFail();

        $rutaFila = ['desguace' => $desguace->id_desguace, 'patio' => $patio->id_patio, 'sector' => $sector->id_sector, 'fila' => $fila->id_fila];
        $this->actingAs($usuario)->post(route('almacen.exterior.posiciones.store'), [
            'id_fila' => $fila->id_fila,
            'numero_posicion' => '1',
            'capacidad_maxima' => 5,
            'ruta' => $rutaFila,
        ])->assertSessionHasNoErrors();
        $posicion = Posicion::where('id_fila', $fila->id_fila)->firstOrFail();
        $this->assertSame(5, (int) $posicion->capacidad_maxima);

        $this->actingAs($usuario)->post(route('almacen.exterior.niveles.store'), [
            'id_posicion' => $posicion->id_posicion,
            'numero_nivel' => '1',
            'ruta' => $rutaFila + ['posicion' => $posicion->id_posicion],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('niveles', ['id_posicion' => $posicion->id_posicion, 'numero_nivel' => '1']);
    }

    public function test_el_alta_valida_campos_y_capacidad(): void
    {
        $e = $this->crearPosicion();
        $usuario = $this->usuarioConRol('gerente');
        $ruta = ['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio, 'sector' => $e['sector']->id_sector, 'fila' => $e['fila']->id_fila];

        foreach (['0', '-3', 'abc', ''] as $capacidad) {
            $this->actingAs($usuario)->post(route('almacen.exterior.posiciones.store'), [
                'id_fila' => $e['fila']->id_fila,
                'numero_posicion' => '2',
                'capacidad_maxima' => $capacidad,
                'ruta' => $ruta,
            ])->assertSessionHasErrors('capacidad_maxima');
        }

        $this->actingAs($usuario)->post(route('almacen.exterior.sectores.store'), [
            'id_patio' => $e['patio']->id_patio,
            'codigo' => 'S-02',
            'tipo_zona' => str_repeat('x', 101),
            'ruta' => ['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio],
        ])->assertSessionHasErrors('tipo_zona');

        $this->assertDatabaseCount('posiciones', 1);
        $this->assertDatabaseCount('sectores', 1);
    }

    public function test_el_padre_debe_pertenecer_al_camino_mostrado(): void
    {
        $e = $this->crearPosicion();
        $sectorAjeno = Sector::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))->post(route('almacen.exterior.filas.store'), [
            'id_sector' => $sectorAjeno->id_sector,
            'numero_fila' => '1',
            'ruta' => ['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio, 'sector' => $sectorAjeno->id_sector],
        ])->assertSessionHasErrors('id_sector');

        $this->assertDatabaseCount('filas', 1);
    }

    public function test_el_operario_no_puede_crear_editar_ni_eliminar(): void
    {
        $e = $this->crearPosicion();
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->post(route('almacen.exterior.patios.store'), [
            'id_desguace' => $e['desguace']->id_desguace, 'codigo' => 'X', 'nombre' => 'X', 'ruta' => ['desguace' => $e['desguace']->id_desguace],
        ])->assertForbidden();
        $this->actingAs($operario)->put(route('almacen.exterior.patios.update', $e['patio']->id_patio), [])->assertForbidden();
        $this->actingAs($operario)->delete(route('almacen.exterior.patios.destroy', $e['patio']->id_patio))->assertForbidden();

        $this->assertDatabaseCount('patios', 1);
        $this->assertNotSoftDeleted('patios', ['id_patio' => $e['patio']->id_patio]);
    }

    // ---------- Edición ----------

    public function test_el_gerente_edita_una_posicion(): void
    {
        $e = $this->crearPosicion(capacidad: 3);
        $ruta = ['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio, 'sector' => $e['sector']->id_sector, 'fila' => $e['fila']->id_fila];

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('almacen.exterior.posiciones.update', $e['posicion']->id_posicion), [
                'id_fila' => $e['fila']->id_fila,
                'numero_posicion' => '77',
                'capacidad_maxima' => 6,
                'ruta' => $ruta,
            ])
            ->assertRedirect($this->urlDePosicion($e))
            ->assertSessionHas('flasher::envelopes');

        $this->assertDatabaseHas('posiciones', ['id_posicion' => $e['posicion']->id_posicion, 'numero_posicion' => '77', 'capacidad_maxima' => 6]);
    }

    public function test_no_se_puede_reducir_la_capacidad_por_debajo_de_los_vehiculos_ubicados(): void
    {
        $e = $this->crearPosicion(capacidad: 3);
        $niveles = Nivel::factory()->count(2)->create(['id_posicion' => $e['posicion']->id_posicion]);
        $this->ubicar($niveles[0]);
        $this->ubicar($niveles[1]);
        $ruta = ['desguace' => $e['desguace']->id_desguace, 'patio' => $e['patio']->id_patio, 'sector' => $e['sector']->id_sector, 'fila' => $e['fila']->id_fila];

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('almacen.exterior.posiciones.update', $e['posicion']->id_posicion), [
                'id_fila' => $e['fila']->id_fila,
                'numero_posicion' => '9',
                'capacidad_maxima' => 1,
                'ruta' => $ruta,
            ])
            ->assertSessionHasErrors('capacidad_maxima');

        $this->assertDatabaseHas('posiciones', ['id_posicion' => $e['posicion']->id_posicion, 'capacidad_maxima' => 3]);
    }

    // ---------- Baja ----------

    public function test_el_administrador_elimina_un_nivel_libre_y_el_gerente_no(): void
    {
        $e = $this->crearPosicion();
        $nivel = Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->delete(route('almacen.exterior.niveles.destroy', $nivel->id_nivel))
            ->assertForbidden();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.exterior.niveles.destroy', $nivel->id_nivel))
            ->assertRedirect($this->urlDeNiveles($e))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSoftDeleted('niveles', ['id_nivel' => $nivel->id_nivel]);
    }

    public function test_no_se_elimina_un_nivel_con_una_ubicacion_vigente(): void
    {
        $e = $this->crearPosicion();
        $nivel = Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion]);
        $this->ubicar($nivel);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.exterior.niveles.destroy', $nivel->id_nivel))
            ->assertSessionHas('flasher::envelopes');

        $this->assertNotSoftDeleted('niveles', ['id_nivel' => $nivel->id_nivel]);
    }

    public function test_un_nivel_con_historial_cerrado_si_se_puede_eliminar(): void
    {
        $e = $this->crearPosicion();
        $nivel = Nivel::factory()->create(['id_posicion' => $e['posicion']->id_posicion]);
        $historica = $this->ubicar($nivel, fin: '2026-02-01');

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.exterior.niveles.destroy', $nivel->id_nivel))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSoftDeleted('niveles', ['id_nivel' => $nivel->id_nivel]);
        $this->assertNotSoftDeleted('ubicaciones_vehiculo', ['id_ubicacion' => $historica->id_ubicacion]);
    }

    public function test_no_se_elimina_un_patio_con_sectores_activos(): void
    {
        $e = $this->crearPosicion();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.exterior.patios.destroy', $e['patio']->id_patio))
            ->assertSessionHas('flasher::envelopes');

        $this->assertNotSoftDeleted('patios', ['id_patio' => $e['patio']->id_patio]);
    }
}
