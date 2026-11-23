<?php

namespace Tests\Feature\Web;

use App\Models\Desguace;
use App\Models\Pieza;
use App\Models\Vehiculo;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Comprobaciones de alcance por sede desde las pantallas web, con dos desguaces (A y B).
 * Las mismas reglas se prueban para la API en Tests\Feature\Api\AccesoPorDesguaceTest.
 */
class AccesoPorDesguaceWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Desguace $sedeA;

    private Desguace $sedeB;

    /** @var array<string, mixed> */
    private array $invA;

    /** @var array<string, mixed> */
    private array $invB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->sedeA = $this->crearSede();
        $this->sedeB = $this->crearSede();
        $this->invA = $this->inventarioDe($this->sedeA);
        $this->invB = $this->inventarioDe($this->sedeB);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPieza(int $idTipo): array
    {
        return [
            'nombre' => 'Faro',
            'precio' => 30,
            'estado' => 'Usada',
            'stock' => 3,
            'stock_minimo' => 1,
            'id_tipo_pieza' => $idTipo,
        ];
    }

    public function test_la_ficha_de_otra_sede_por_url_directa_se_rechaza(): void
    {
        $vehiculoB = $this->invB['vehiculo'];
        $piezaB = $this->invB['pieza'];

        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::GERENTE, RoleSeeder::OPERARIO] as $rol) {
            $usuario = $this->usuarioConRol($rol, $this->sedeA);

            $this->actingAs($usuario)->get(route('vehiculos.show', $vehiculoB))->assertNotFound();
            $this->actingAs($usuario)->get(route('vehiculos.ubicaciones.index', $vehiculoB))->assertNotFound();
            $this->actingAs($usuario)->get(route('piezas.show', $piezaB))->assertNotFound();

            // La propia sede sí responde.
            $this->actingAs($usuario)->get(route('vehiculos.show', $this->invA['vehiculo']))->assertOk();
            $this->actingAs($usuario)->get(route('piezas.show', $this->invA['pieza']))->assertOk();
        }

        $administrador = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $this->actingAs($administrador)->get(route('vehiculos.edit', $vehiculoB))->assertNotFound();
        $this->actingAs($administrador)->put(route('vehiculos.update', $vehiculoB), $this->datosVehiculo($this->sedeA))->assertNotFound();
        $this->actingAs($administrador)->delete(route('vehiculos.destroy', $vehiculoB))->assertNotFound();
        $this->actingAs($administrador)->get(route('piezas.edit', $piezaB))->assertNotFound();
        $this->actingAs($administrador)->delete(route('piezas.destroy', $piezaB))->assertNotFound();
        $this->actingAs($administrador)->get(route('vehiculos.ubicaciones.asignar', $vehiculoB))->assertNotFound();

        $this->assertNull(Vehiculo::withoutGlobalScopes()->find($vehiculoB->id_vehiculo)->deleted_at);
        $this->assertNull(Pieza::withoutGlobalScopes()->find($piezaB->id_pieza)->deleted_at);
    }

    public function test_los_listados_y_busquedas_no_muestran_la_otra_sede(): void
    {
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, $this->sedeA);

        $this->actingAs($gerente)->get(route('vehiculos.index'))
            ->assertSee($this->invA['vehiculo']->matricula)
            ->assertDontSee($this->invB['vehiculo']->matricula);

        // Buscar la matrícula de la otra sede no devuelve resultados.
        $this->actingAs($gerente)->get(route('vehiculos.index', ['q' => $this->invB['vehiculo']->matricula]))
            ->assertViewHas('vehiculos', fn ($vehiculos) => $vehiculos->total() === 0);

        $this->actingAs($gerente)->get(route('piezas.index'))
            ->assertViewHas('piezas', fn ($piezas) => $piezas->pluck('id_pieza')->all() === [$this->invA['pieza']->id_pieza]);
    }

    public function test_un_formulario_con_un_identificador_de_otra_sede_se_rechaza(): void
    {
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, $this->sedeA);

        // Vehículo dado de alta en la otra sede.
        $this->actingAs($gerente)
            ->post(route('vehiculos.store'), $this->datosVehiculo($this->sedeB))
            ->assertSessionHasErrors('id_desguace');

        // Vehículo propio movido a la otra sede al editar.
        $this->actingAs($gerente)
            ->put(route('vehiculos.update', $this->invA['vehiculo']), $this->datosVehiculo($this->sedeB))
            ->assertSessionHasErrors('id_desguace');

        // Pieza con un tipo de pieza de la otra sede.
        $this->actingAs($gerente)
            ->post(route('piezas.store'), $this->datosPieza($this->invB['tipo']->id_pieza))
            ->assertSessionHasErrors('id_tipo_pieza');

        // Ubicación de un vehículo propio en un nivel de la otra sede.
        $this->actingAs($gerente)
            ->post(route('vehiculos.ubicaciones.store', $this->invA['vehiculo']), [
                'id_nivel' => $this->invB['nivel']->id_nivel,
                'fecha_inicio' => '2026-05-01',
            ])
            ->assertSessionHasErrors('id_nivel');

        // Elemento del almacén colgado de un módulo de la otra sede.
        $this->actingAs($gerente)
            ->post(route('almacen.interior.pasillos.store'), [
                'id_modulo' => $this->invB['modulo']->id_modulo,
                'numero_pasillo' => '9',
                'ruta' => ['desguace' => $this->sedeB->id_desguace, 'modulo' => $this->invB['modulo']->id_modulo],
            ])
            ->assertSessionHasErrors('id_modulo');

        $this->assertSame($this->sedeA->id_desguace, $this->invA['vehiculo']->fresh()->id_desguace);
        $this->assertSame(1, Vehiculo::withoutGlobalScopes()->where('id_desguace', $this->sedeB->id_desguace)->count());
        $this->assertSame(1, Pieza::withoutGlobalScopes()->count() - 1);
    }

    public function test_un_operario_consulta_pero_no_modifica(): void
    {
        $operario = $this->usuarioConRol(RoleSeeder::OPERARIO, $this->sedeA);
        $vehiculo = $this->invA['vehiculo'];
        $pieza = $this->invA['pieza'];

        $this->actingAs($operario)->get(route('vehiculos.index'))->assertOk();
        $this->actingAs($operario)->get(route('vehiculos.show', $vehiculo))->assertOk()->assertDontSee(route('vehiculos.edit', $vehiculo));
        $this->actingAs($operario)->get(route('piezas.index'))->assertOk();
        $this->actingAs($operario)->get(route('piezas.show', $pieza))->assertOk()->assertDontSee(route('piezas.edit', $pieza));

        $this->actingAs($operario)->get(route('vehiculos.create'))->assertForbidden();
        $this->actingAs($operario)->post(route('vehiculos.store'), $this->datosVehiculo($this->sedeA))->assertForbidden();
        $this->actingAs($operario)->put(route('vehiculos.update', $vehiculo), $this->datosVehiculo($this->sedeA))->assertForbidden();
        $this->actingAs($operario)->delete(route('vehiculos.destroy', $vehiculo))->assertForbidden();
        $this->actingAs($operario)->put(route('piezas.update', $pieza), $this->datosPieza($this->invA['tipo']->id_pieza) + ['stock' => 99])->assertForbidden();
        $this->actingAs($operario)->delete(route('piezas.destroy', $pieza))->assertForbidden();

        foreach (['movimientos.index', 'categorias.index', 'desguaces.index', 'almacen.interior.index', 'admin.usuarios.index'] as $ruta) {
            $this->actingAs($operario)->get(route($ruta))->assertForbidden();
        }

        $this->assertSame(5, $pieza->fresh()->stock);
        $this->assertNull($vehiculo->fresh()->deleted_at);
    }

    public function test_el_menu_se_adapta_a_cada_rol(): void
    {
        $casos = [
            RoleSeeder::PLATAFORMA => [null, ['desguaces.index', 'admin.usuarios.index'], ['vehiculos.index', 'piezas.index', 'almacen.interior.index']],
            RoleSeeder::ADMIN_DESGUACE => [$this->sedeA, ['vehiculos.index', 'piezas.index', 'almacen.interior.index', 'admin.usuarios.index'], []],
            RoleSeeder::GERENTE => [$this->sedeA, ['vehiculos.index', 'piezas.index', 'almacen.interior.index'], ['admin.usuarios.index']],
            RoleSeeder::OPERARIO => [$this->sedeA, ['vehiculos.index', 'piezas.index'], ['movimientos.index', 'categorias.index', 'desguaces.index', 'almacen.interior.index', 'admin.usuarios.index']],
        ];

        foreach ($casos as $rol => [$sede, $visibles, $ocultas]) {
            $this->app['auth']->forgetGuards();
            $pagina = $this->actingAs($this->usuarioConRol($rol, $sede))->get(route('dashboard'))->assertOk();

            foreach ($visibles as $ruta) {
                $pagina->assertSee('href="'.route($ruta).'"', false);
            }
            foreach ($ocultas as $ruta) {
                $pagina->assertDontSee('href="'.route($ruta).'"', false);
            }
        }
    }
}
