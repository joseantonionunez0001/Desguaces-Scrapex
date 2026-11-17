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
use Illuminate\Support\Facades\Route;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AsignacionUbicacionTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Desguace $desguace;

    private Vehiculo $vehiculo;

    private Patio $patio;

    private Sector $sector;

    private Fila $fila;

    private Posicion $posicion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->desguace = Desguace::factory()->create();
        $this->vehiculo = Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace, 'matricula' => '4321KLM']);
        $this->patio = Patio::factory()->create(['id_desguace' => $this->desguace->id_desguace, 'codigo' => 'PAT-A', 'nombre' => 'Patio A']);
        $this->sector = Sector::factory()->create(['id_patio' => $this->patio->id_patio, 'codigo' => 'S-A', 'tipo_zona' => 'Motores']);
        $this->fila = Fila::factory()->create(['id_sector' => $this->sector->id_sector, 'numero_fila' => '2']);
        $this->posicion = Posicion::factory()->create(['id_fila' => $this->fila->id_fila, 'numero_posicion' => '5', 'capacidad_maxima' => 3]);
    }

    private function nivel(?Posicion $posicion = null, string $numero = '1'): Nivel
    {
        return Nivel::factory()->create([
            'id_posicion' => ($posicion ?? $this->posicion)->id_posicion,
            'numero_nivel' => $numero,
        ]);
    }

    private function ubicar(Vehiculo $vehiculo, Nivel $nivel, string $inicio, ?string $fin = null): UbicacionVehiculo
    {
        return UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
        ]);
    }

    private function urlAsignar(?Vehiculo $vehiculo = null): string
    {
        return route('vehiculos.ubicaciones.asignar', $vehiculo ?? $this->vehiculo);
    }

    /**
     * Envía el formulario de asignación (con los pasos de la cascada) desde su propia pantalla.
     *
     * @param  array<string, mixed>  $datos
     */
    private function enviar(?string $rol, array $datos, ?Vehiculo $vehiculo = null)
    {
        $vehiculo ??= $this->vehiculo;

        return $this->actingAs($this->usuarioConRol($rol))
            ->from($this->urlAsignar($vehiculo))
            ->post(route('vehiculos.ubicaciones.store', $vehiculo), $datos + [
                'patio' => $this->patio->id_patio,
                'sector' => $this->sector->id_sector,
                'fila' => $this->fila->id_fila,
                'posicion' => $this->posicion->id_posicion,
            ]);
    }

    private function urlConCascada(): string
    {
        return $this->urlAsignar().'?'.http_build_query([
            'patio' => $this->patio->id_patio,
            'sector' => $this->sector->id_sector,
            'fila' => $this->fila->id_fila,
            'posicion' => $this->posicion->id_posicion,
        ]);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get($this->urlAsignar())->assertRedirect(route('login'));
        $this->post(route('vehiculos.ubicaciones.store', $this->vehiculo), [])->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $this->actingAs($this->usuarioConRol(null))->get($this->urlAsignar())->assertForbidden();
        $this->enviar(null, ['id_nivel' => $this->nivel()->id_nivel, 'fecha_inicio' => '2026-05-01'])->assertForbidden();
        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_el_operario_no_puede_ver_ni_asignar(): void
    {
        $nivel = $this->nivel();

        $this->actingAs($this->usuarioConRol('operario'))->get($this->urlAsignar())->assertForbidden();
        $this->enviar('operario', ['id_nivel' => $nivel->id_nivel, 'fecha_inicio' => '2026-05-01'])->assertForbidden();

        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_gerente_y_administrador_ven_la_pantalla_con_el_vehiculo(): void
    {
        foreach (['gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get($this->urlAsignar())
                ->assertOk()
                ->assertSee('Asignar ubicación')
                ->assertSee('4321KLM')
                ->assertSee('Este vehículo no tiene ninguna ubicación vigente.');
        }
    }

    public function test_un_vehiculo_inexistente_o_eliminado_devuelve_404(): void
    {
        $administrador = $this->usuarioConRol('administrador');
        $eliminado = Vehiculo::factory()->create();
        $eliminado->delete();

        $this->actingAs($administrador)->get(route('vehiculos.ubicaciones.asignar', 99999))->assertNotFound();
        $this->actingAs($administrador)->get($this->urlAsignar($eliminado))->assertNotFound();
    }

    public function test_muestra_la_ubicacion_vigente_y_el_historial_reciente(): void
    {
        $this->ubicar($this->vehiculo, $this->nivel(null, '3'), '2026-01-01', '2026-01-31');
        $this->ubicar($this->vehiculo, $this->nivel(null, '4'), '2026-02-01');

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlAsignar())
            ->assertOk()
            ->assertSee('Patio PAT-A · Sector S-A · Fila 2 · Posición 5 · Nivel 4')
            ->assertSee('Desde el 01/02/2026')
            ->assertSee('Nivel 3')
            ->assertSee('Es un traslado');
    }

    // ---------- Cascada ----------

    public function test_la_cascada_ofrece_solo_los_patios_del_desguace_del_vehiculo(): void
    {
        Patio::factory()->create(['codigo' => 'PAT-AJENO', 'nombre' => 'Patio ajeno']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlAsignar())
            ->assertOk()
            ->assertSee('PAT-A · Patio A')
            ->assertDontSee('PAT-AJENO');
    }

    public function test_los_pasos_de_la_cascada_se_resuelven_por_get(): void
    {
        $this->nivel(null, '8');
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->get($this->urlAsignar().'?patio='.$this->patio->id_patio)
            ->assertSee('S-A · Motores')
            ->assertSee('Elige antes un sector')
            ->assertDontSee('Nivel 8');

        $this->actingAs($gerente)->get($this->urlConCascada())
            ->assertSee('Posición 5 (0/3 ocupados)')
            ->assertSee('Nivel 8 · libre')
            ->assertSee('Guardar ubicación');
    }

    public function test_un_paso_que_no_pertenece_al_anterior_se_ignora(): void
    {
        $sectorAjeno = Sector::factory()->create(['codigo' => 'S-AJENO']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlAsignar().'?'.http_build_query(['patio' => $this->patio->id_patio, 'sector' => $sectorAjeno->id_sector]))
            ->assertOk()
            ->assertDontSee('Guardar ubicación')
            ->assertSee('Elige un sector');
    }

    public function test_sin_patios_muestra_el_estado_vacio(): void
    {
        $this->patio->delete();

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlAsignar())
            ->assertOk()
            ->assertSee('El desguace del vehículo no tiene patios');
    }

    // ---------- Asignación y traslado ----------

    public function test_asigna_la_primera_ubicacion_a_un_vehiculo_sin_historial(): void
    {
        $nivel = $this->nivel();

        // Con la rama de vehículos integrada se redirige al historial; sin ella, de vuelta a la asignación.
        $destino = Route::has('vehiculos.ubicaciones.index')
            ? route('vehiculos.ubicaciones.index', $this->vehiculo)
            : $this->urlAsignar();

        $this->enviar('gerente', ['id_nivel' => $nivel->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertRedirect($destino)
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $ubicacion = UbicacionVehiculo::where('id_vehiculo', $this->vehiculo->id_vehiculo)->sole();
        $this->assertSame($nivel->id_nivel, $ubicacion->id_nivel);
        $this->assertSame('2026-05-01', $ubicacion->fecha_inicio->toDateString());
        $this->assertNull($ubicacion->fecha_fin);
    }

    public function test_el_administrador_tambien_puede_asignar(): void
    {
        $this->enviar('administrador', ['id_nivel' => $this->nivel()->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ubicaciones_vehiculo', 1);
    }

    public function test_el_traslado_conserva_el_historial(): void
    {
        $origen = $this->nivel(null, '1');
        $destino = $this->nivel(null, '2');
        $anterior = $this->ubicar($this->vehiculo, $origen, '2026-01-01');

        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-03-10'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        // La fila anterior sigue existiendo (sin borrado lógico) y queda cerrada el día anterior.
        $anterior->refresh();
        $this->assertNull($anterior->deleted_at);
        $this->assertSame($origen->id_nivel, $anterior->id_nivel);
        $this->assertSame('2026-01-01', $anterior->fecha_inicio->toDateString());
        $this->assertSame('2026-03-09', $anterior->fecha_fin->toDateString());

        // Y hay una nueva ubicación vigente.
        $this->assertDatabaseCount('ubicaciones_vehiculo', 2);
        $vigente = UbicacionVehiculo::where('id_vehiculo', $this->vehiculo->id_vehiculo)->whereNull('fecha_fin')->sole();
        $this->assertSame($destino->id_nivel, $vigente->id_nivel);
        $this->assertSame('2026-03-10', $vigente->fecha_inicio->toDateString());
    }

    public function test_tras_un_traslado_la_pantalla_muestra_ambas_ubicaciones(): void
    {
        $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-01-01');
        $destino = $this->nivel(null, '2');

        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-03-10']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlAsignar())
            ->assertSee('Nivel 1')
            ->assertSee('09/03/2026')
            ->assertSee('Nivel 2')
            ->assertSee('Vigente');
    }

    public function test_se_puede_asignar_con_fecha_de_fin(): void
    {
        $this->enviar('gerente', ['id_nivel' => $this->nivel()->id_nivel, 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-05-20'])
            ->assertSessionHasNoErrors();

        $ubicacion = UbicacionVehiculo::sole();
        $this->assertSame('2026-05-20', $ubicacion->fecha_fin->toDateString());
    }

    // ---------- Validaciones ----------

    public function test_la_fecha_de_inicio_y_el_nivel_son_obligatorios(): void
    {
        $this->enviar('gerente', [])
            ->assertRedirect($this->urlConCascada())
            ->assertSessionHasErrors(['id_nivel', 'fecha_inicio']);

        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_la_fecha_de_fin_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        $this->enviar('gerente', ['id_nivel' => $this->nivel()->id_nivel, 'fecha_inicio' => '2026-05-10', 'fecha_fin' => '2026-05-09'])
            ->assertSessionHasErrors('fecha_fin');

        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_un_nivel_inexistente_o_eliminado_no_es_valido(): void
    {
        $eliminado = $this->nivel();
        $eliminado->delete();

        $this->enviar('gerente', ['id_nivel' => 99999, 'fecha_inicio' => '2026-05-01'])->assertSessionHasErrors('id_nivel');
        $this->enviar('gerente', ['id_nivel' => $eliminado->id_nivel, 'fecha_inicio' => '2026-05-01'])->assertSessionHasErrors('id_nivel');
        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_no_permite_un_nivel_de_un_patio_de_otro_desguace(): void
    {
        $otroDesguace = Desguace::factory()->create();
        $patioAjeno = Patio::factory()->create(['id_desguace' => $otroDesguace->id_desguace]);
        $sectorAjeno = Sector::factory()->create(['id_patio' => $patioAjeno->id_patio]);
        $filaAjena = Fila::factory()->create(['id_sector' => $sectorAjeno->id_sector]);
        $posicionAjena = Posicion::factory()->create(['id_fila' => $filaAjena->id_fila, 'capacidad_maxima' => 5]);
        $nivelAjeno = $this->nivel($posicionAjena);

        $this->enviar('gerente', ['id_nivel' => $nivelAjeno->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertSessionHasErrors(['id_nivel' => 'El nivel elegido pertenece a un patio de otro desguace distinto al del vehículo.']);

        $this->assertDatabaseCount('ubicaciones_vehiculo', 0);
    }

    public function test_no_permite_la_misma_ubicacion_que_la_vigente(): void
    {
        $nivel = $this->nivel();
        $this->ubicar($this->vehiculo, $nivel, '2026-01-01');

        $this->enviar('gerente', ['id_nivel' => $nivel->id_nivel, 'fecha_inicio' => '2026-03-01'])
            ->assertSessionHasErrors('id_nivel');

        $this->assertDatabaseCount('ubicaciones_vehiculo', 1);
        $this->assertNull(UbicacionVehiculo::sole()->fecha_fin);
    }

    public function test_el_traslado_debe_empezar_despues_del_inicio_de_la_vigente(): void
    {
        $vigente = $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-03-10');
        $destino = $this->nivel(null, '2');

        foreach (['2026-03-10', '2026-03-01'] as $fecha) {
            $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => $fecha])
                ->assertSessionHasErrors('fecha_inicio');
        }

        $this->assertDatabaseCount('ubicaciones_vehiculo', 1);
        $this->assertNull($vigente->fresh()->fecha_fin);
    }

    public function test_un_dia_despues_del_inicio_de_la_vigente_es_suficiente(): void
    {
        $vigente = $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-03-10');

        $this->enviar('gerente', ['id_nivel' => $this->nivel(null, '2')->id_nivel, 'fecha_inicio' => '2026-03-11'])
            ->assertSessionHasNoErrors();

        // La vigente se cierra el mismo día en que empezó (fechas inclusivas, sin solape).
        $this->assertSame('2026-03-10', $vigente->fresh()->fecha_fin->toDateString());
    }

    public function test_no_permite_solapar_fechas_con_el_historial_cerrado(): void
    {
        $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-01-01', '2026-01-31');
        $destino = $this->nivel(null, '2');

        // Dentro del intervalo cerrado, justo el último día (inclusivo) y con fin que lo invade.
        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-01-15'])->assertSessionHasErrors('fecha_inicio');
        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-01-31'])->assertSessionHasErrors('fecha_inicio');
        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2025-12-20', 'fecha_fin' => '2026-01-02'])->assertSessionHasErrors('fecha_inicio');

        $this->assertDatabaseCount('ubicaciones_vehiculo', 1);

        // El día siguiente al cierre sí es válido.
        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-02-01'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ubicaciones_vehiculo', 2);
    }

    public function test_un_intervalo_cerrado_anterior_al_historial_no_solapa(): void
    {
        $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-03-01', '2026-03-31');

        $this->enviar('gerente', ['id_nivel' => $this->nivel(null, '2')->id_nivel, 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-02-28'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ubicaciones_vehiculo', 2);
    }

    // ---------- Capacidad y ocupación ----------

    public function test_no_se_supera_la_capacidad_maxima_de_la_posicion(): void
    {
        $this->posicion->update(['capacidad_maxima' => 2]);
        $this->ubicar(Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace]), $this->nivel(null, '1'), '2026-01-01');
        $this->ubicar(Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace]), $this->nivel(null, '2'), '2026-01-01');
        $libre = $this->nivel(null, '3');

        $this->enviar('gerente', ['id_nivel' => $libre->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertSessionHasErrors(['id_nivel' => 'La posición 5 está completa (2 de 2 plazas ocupadas).']);

        $this->assertDatabaseMissing('ubicaciones_vehiculo', ['id_vehiculo' => $this->vehiculo->id_vehiculo]);
    }

    public function test_las_ubicaciones_cerradas_no_cuentan_para_la_capacidad(): void
    {
        $this->posicion->update(['capacidad_maxima' => 1]);
        $otro = Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace]);
        $this->ubicar($otro, $this->nivel(null, '1'), '2026-01-01', '2026-02-01');

        $this->enviar('gerente', ['id_nivel' => $this->nivel(null, '2')->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ubicaciones_vehiculo', ['id_vehiculo' => $this->vehiculo->id_vehiculo]);
    }

    public function test_mover_dentro_de_una_posicion_completa_no_cuenta_la_propia_ubicacion(): void
    {
        $this->posicion->update(['capacidad_maxima' => 1]);
        $this->ubicar($this->vehiculo, $this->nivel(null, '1'), '2026-01-01');
        $destino = $this->nivel(null, '2');

        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-03-01'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ubicaciones_vehiculo', 2);
    }

    public function test_un_error_de_negocio_no_cierra_la_ubicacion_vigente(): void
    {
        $this->posicion->update(['capacidad_maxima' => 1]);
        $origen = $this->nivel(null, '1');
        $vigente = $this->ubicar($this->vehiculo, $origen, '2026-01-01');

        // Otra posición completa a la que se intenta trasladar.
        $posicionLlena = Posicion::factory()->create(['id_fila' => $this->fila->id_fila, 'numero_posicion' => '6', 'capacidad_maxima' => 1]);
        $this->ubicar(Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace]), $this->nivel($posicionLlena, '1'), '2026-01-01');
        $destino = $this->nivel($posicionLlena, '2');

        $this->enviar('gerente', ['id_nivel' => $destino->id_nivel, 'fecha_inicio' => '2026-03-01', 'posicion' => $posicionLlena->id_posicion])
            ->assertSessionHasErrors('id_nivel');

        $this->assertNull($vigente->fresh()->fecha_fin);
        $this->assertDatabaseCount('ubicaciones_vehiculo', 2);
    }

    public function test_un_nivel_ocupado_por_otro_vehiculo_no_se_puede_asignar(): void
    {
        $ocupado = $this->nivel();
        $this->ubicar(Vehiculo::factory()->create(['id_desguace' => $this->desguace->id_desguace]), $ocupado, '2026-01-01');

        $this->enviar('gerente', ['id_nivel' => $ocupado->id_nivel, 'fecha_inicio' => '2026-05-01'])
            ->assertSessionHasErrors(['id_nivel' => 'El nivel 1 ya está ocupado por otro vehículo.']);

        $this->assertDatabaseMissing('ubicaciones_vehiculo', ['id_vehiculo' => $this->vehiculo->id_vehiculo]);
    }

    // ---------- Errores junto al formulario ----------

    public function test_los_errores_se_muestran_junto_al_formulario_en_el_mismo_paso(): void
    {
        $nivel = $this->nivel();
        $this->ubicar($this->vehiculo, $nivel, '2026-01-01');

        $this->followingRedirects()
            ->actingAs($this->usuarioConRol('gerente'))
            ->from($this->urlConCascada())
            ->post(route('vehiculos.ubicaciones.store', $this->vehiculo), [
                'id_nivel' => $nivel->id_nivel,
                'fecha_inicio' => '2026-03-01',
                'patio' => $this->patio->id_patio,
                'sector' => $this->sector->id_sector,
                'fila' => $this->fila->id_fila,
                'posicion' => $this->posicion->id_posicion,
            ])
            ->assertOk()
            ->assertSee('El vehículo ya está ubicado en ese nivel. Elige un nivel distinto para trasladarlo.')
            ->assertSee('Guardar ubicación')
            ->assertSee('value="2026-03-01"', false);
    }

    public function test_los_errores_de_validacion_vuelven_al_paso_de_la_cascada(): void
    {
        $this->enviar('gerente', ['id_nivel' => $this->nivel()->id_nivel, 'fecha_inicio' => 'no-es-fecha'])
            ->assertRedirect($this->urlConCascada())
            ->assertSessionHasErrors('fecha_inicio');
    }
}
