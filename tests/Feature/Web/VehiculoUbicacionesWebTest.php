<?php

namespace Tests\Feature\Web;

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

/**
 * Historial de ubicaciones de un vehículo (pantalla de solo lectura).
 */
class VehiculoUbicacionesWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    /**
     * Crea la jerarquía patio › sector › fila › posición › nivel con valores reconocibles.
     */
    private function crearNivel(string $codigoPatio, string $codigoSector, string $fila, string $posicion, string $nivel): Nivel
    {
        $patio = Patio::factory()->create(['nombre' => 'Patio '.$codigoPatio, 'codigo' => $codigoPatio]);
        $sector = Sector::factory()->create(['id_patio' => $patio->id_patio, 'codigo' => $codigoSector]);
        $filaCreada = Fila::factory()->create(['id_sector' => $sector->id_sector, 'numero_fila' => $fila]);
        $posicionCreada = Posicion::factory()->create(['id_fila' => $filaCreada->id_fila, 'numero_posicion' => $posicion]);

        return Nivel::factory()->create(['id_posicion' => $posicionCreada->id_posicion, 'numero_nivel' => $nivel]);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->get(route('vehiculos.ubicaciones.index', $vehiculo))->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol(null))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertForbidden();
    }

    public function test_hacen_falta_los_dos_permisos_vehiculos_ver_y_ubicaciones_vehiculo_ver(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $soloVehiculos = $this->usuarioConRol(null);
        $soloVehiculos->givePermissionTo('vehiculos.ver');
        $this->actingAs($soloVehiculos)
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertForbidden();

        $soloUbicaciones = $this->usuarioConRol(null);
        $soloUbicaciones->givePermissionTo('ubicaciones_vehiculo.ver');
        $this->actingAs($soloUbicaciones)
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertForbidden();

        $ambos = $this->usuarioConRol(null);
        $ambos->givePermissionTo(['vehiculos.ver', 'ubicaciones_vehiculo.ver']);
        $this->actingAs($ambos)
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk();
    }

    public function test_cada_rol_autorizado_ve_el_historial_con_la_cabecera_del_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create(['matricula' => '1010LLL', 'marca' => 'Volkswagen', 'modelo' => 'Golf']);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('vehiculos.ubicaciones.index', $vehiculo))
                ->assertOk()
                ->assertSee('1010LLL')
                ->assertSee('Volkswagen Golf');
        }
    }

    public function test_el_historial_incluye_las_ubicaciones_cerradas_y_la_vigente_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $nivelAntiguo = $this->crearNivel('ANT', 'S-AN', '1', '11', '1');
        $nivelReciente = $this->crearNivel('REC', 'S-RE', '2', '22', '2');
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivelAntiguo->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-11',
        ]);
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivelReciente->id_nivel,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin' => null,
        ]);

        $respuesta = $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk();

        // La tabla va en orden cronológico descendente: primero la vigente y después la cerrada.
        $respuesta->assertSeeInOrder(['Historial', 'Vigente', '5 días', 'Patio REC (REC)', 'Sector S-RE', 'Fila 2', 'Posición 22', 'Nivel 2'])
            ->assertSeeInOrder(['Patio REC (REC)', '01/01/2026', '11/01/2026', '10 días', 'Patio ANT (ANT)', 'Sector S-AN', 'Fila 1', 'Posición 11', 'Nivel 1']);
        $this->assertSame(
            [$nivelReciente->id_nivel, $nivelAntiguo->id_nivel],
            $respuesta->viewData('ubicaciones')->pluck('id_nivel')->all(),
        );
        $this->assertSame($nivelReciente->id_nivel, $respuesta->viewData('ubicacionActual')->id_nivel);
    }

    public function test_el_historial_conserva_la_jerarquia_aunque_se_haya_borrado_logicamente(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $nivel = $this->crearNivel('BOR', 'S-BO', '8', '88', '4');
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-02-02',
        ]);

        // Se borran lógicamente el nivel y todos los elementos superiores de la jerarquía.
        $posicion = $nivel->posicion;
        $fila = $posicion->fila;
        $sector = $fila->sector;
        $patio = $sector->patio;
        $nivel->delete();
        $posicion->delete();
        $fila->delete();
        $sector->delete();
        $patio->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk()
            ->assertSeeInOrder(['Patio BOR (BOR)', 'Sector S-BO', 'Fila 8', 'Posición 88', 'Nivel 4'])
            ->assertSee('(eliminado)');
    }

    public function test_un_vehiculo_sin_ubicaciones_muestra_el_aviso_y_el_estado_vacio(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk()
            ->assertSee('no tiene una ubicación vigente')
            ->assertSee('Sin historial de ubicaciones');
    }

    public function test_el_historial_solo_incluye_las_ubicaciones_del_vehiculo_consultado(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $otro = Vehiculo::factory()->create();
        $nivelAjeno = $this->crearNivel('AJE', 'S-AJ', '9', '99', '5');
        UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $otro->id_vehiculo,
            'id_nivel' => $nivelAjeno->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
        ]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk()
            ->assertDontSee('Patio AJE')
            ->assertSee('Sin historial de ubicaciones');
    }

    public function test_el_historial_es_de_solo_lectura(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $nivel = $this->crearNivel('LEC', 'S-LE', '1', '1', '1');
        $ubicacion = UbicacionVehiculo::factory()->create([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'id_nivel' => $nivel->id_nivel,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
        ]);
        $this->actingAs($this->usuarioConRol('administrador'));

        $this->get(route('vehiculos.ubicaciones.index', $vehiculo))
            ->assertOk()
            ->assertDontSee('name="_method"', false);
        $this->post(route('vehiculos.ubicaciones.index', $vehiculo))->assertStatus(405);
        $this->delete(route('vehiculos.ubicaciones.index', $vehiculo))->assertStatus(405);
        $this->assertNotSoftDeleted('ubicaciones_vehiculo', ['id_ubicacion' => $ubicacion->id_ubicacion]);
    }

    public function test_el_historial_de_un_vehiculo_borrado_devuelve_404(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $vehiculo->delete();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('vehiculos.ubicaciones.index', $vehiculo->id_vehiculo))
            ->assertNotFound();
    }
}
