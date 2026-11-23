<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\ExistenciaPieza;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Models\User;
use App\Services\InventarioPiezas;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantallas web de movimientos: listado del historial con filtros, registro en dos pasos (pieza y acción)
 * y permisos. Las reglas de existencias se prueban en InventarioPiezasWebTest.
 */
class MovimientosWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Categoria $categoria;

    private Pieza $pieza;

    private Etiqueta $hueco;

    private User $gerente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->gerente = $this->usuarioConRol(RoleSeeder::GERENTE);
        $this->hueco = $this->hueco('A-01');
        $this->categoria = Categoria::factory()->create(['nombre' => 'Motor', 'id_modulo' => null]);
        $tipo = TipoPieza::factory()->create(['id_categoria' => $this->categoria->categoria_id]);
        $this->pieza = app(InventarioPiezas::class)->darDeAlta(
            ['nombre' => 'Radiador', 'precio' => 50, 'estado' => 'Buen estado', 'stock_minimo' => 1, 'id_tipo_pieza' => $tipo->id_pieza],
            6,
            $this->hueco->id_etiqueta,
            $this->gerente,
            (string) Str::uuid(),
        );
    }

    private function registrar(string $accion, array $datos): void
    {
        app(InventarioPiezas::class)->registrar($this->pieza->fresh(), $accion, $datos, $this->gerente, (string) Str::uuid());
    }

    // ---------- Acceso ----------

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('movimientos.index'))->assertRedirect(route('login'));
        $this->get(route('movimientos.create'))->assertRedirect(route('login'));
        $this->post(route('movimientos.store', $this->pieza), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_y_operario_reciben_403(): void
    {
        foreach ([null, RoleSeeder::OPERARIO] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            $this->actingAs($usuario)->get(route('movimientos.index'))->assertForbidden();
            $this->actingAs($usuario)->get(route('movimientos.create'))->assertForbidden();
            $this->actingAs($usuario)->post(route('movimientos.store', $this->pieza), [
                'accion' => 'venta', 'cantidad' => 1, 'origen' => (string) $this->hueco->id_etiqueta, 'clave_envio' => (string) Str::uuid(),
            ])->assertForbidden();
        }

        $this->assertSame(6, $this->pieza->fresh()->stock);
    }

    public function test_el_historial_no_se_edita_ni_se_borra(): void
    {
        $movimiento = Movimiento::firstOrFail();
        $administrador = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        // No existen rutas de edición ni de borrado de movimientos.
        $this->assertFalse(Route::has('movimientos.destroy'));
        $this->assertFalse(Route::has('movimientos.update'));
        $this->actingAs($administrador)->delete('/movimientos/'.$movimiento->id_movimiento)->assertNotFound();
        $this->actingAs($administrador)->put('/movimientos/'.$movimiento->id_movimiento, [])->assertNotFound();

        $this->assertNotSoftDeleted($movimiento);
    }

    // ---------- Listado ----------

    public function test_el_listado_muestra_accion_pieza_cantidad_huecos_saldo_y_usuario(): void
    {
        $otroHueco = $this->hueco('B-02');
        $this->registrar(Movimiento::TRASLADO, ['cantidad' => 2, 'origen' => $this->hueco->id_etiqueta, 'destino' => $otroHueco->id_etiqueta]);

        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get(route('movimientos.index'))
                ->assertOk()
                ->assertSeeInOrder(['Mover pieza', 'Radiador', '2', $this->hueco->rutaCompleta(), $otroHueco->rutaCompleta(), '6', '6', $this->gerente->name])
                ->assertSeeInOrder(['Entrada', 'Radiador', '6', '0', '6']);
        }
    }

    public function test_los_movimientos_antiguos_se_muestran_como_historicos(): void
    {
        Movimiento::factory()->create(['tipo_movimiento' => 'Salida', 'cantidad' => 3, 'id_categoria' => $this->categoria->categoria_id]);

        $this->actingAs($this->gerente)->get(route('movimientos.index'))
            ->assertOk()
            ->assertSee('Histórico · Salida')
            ->assertSee('Motor');
    }

    public function test_estado_vacio_y_sin_resultados(): void
    {
        Movimiento::query()->forceDelete();

        $this->actingAs($this->gerente)->get(route('movimientos.index'))->assertSee('Todavía no hay movimientos');

        $this->registrar(Movimiento::COMPRA, ['cantidad' => 1, 'destino' => $this->hueco->id_etiqueta]);
        $this->actingAs($this->gerente)->get(route('movimientos.index', ['q' => 'no-existe']))
            ->assertSee('Sin resultados')
            ->assertSee('Limpiar filtros');
    }

    public function test_los_filtros_por_accion_busqueda_y_fechas_se_combinan(): void
    {
        $this->registrar(Movimiento::VENTA, ['cantidad' => 1, 'origen' => $this->hueco->id_etiqueta]);
        $this->registrar(Movimiento::BAJA, ['cantidad' => 1, 'origen' => $this->hueco->id_etiqueta, 'motivo' => 'Golpe en el embalaje']);
        $hoy = now()->toDateString();

        $total = fn (array $filtros) => $this->actingAs($this->gerente)->get(route('movimientos.index', $filtros))->viewData('movimientos')->total();

        $this->assertSame(3, $total([]));
        $this->assertSame(1, $total(['accion' => 'venta']));
        $this->assertSame(1, $total(['q' => 'embalaje']));
        $this->assertSame(3, $total(['q' => 'Radiador']));
        $this->assertSame(1, $total(['accion' => 'baja', 'q' => 'Radiador', 'desde' => $hoy, 'hasta' => $hoy]));
        $this->assertSame(0, $total(['accion' => 'venta', 'hasta' => now()->subDay()->toDateString()]));
    }

    public function test_los_parametros_de_filtro_mal_formados_se_ignoran(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('movimientos.index', ['accion' => 'inventada', 'desde' => '2026-02-30', 'hasta' => 'ayer', 'q' => ['x']]))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->total() === 1);
    }

    public function test_el_listado_se_pagina_de_15_en_15_conservando_filtros(): void
    {
        foreach (range(1, 16) as $numero) {
            $this->registrar(Movimiento::COMPRA, ['cantidad' => 1, 'destino' => $this->hueco->id_etiqueta]);
        }

        $primera = $this->actingAs($this->gerente)->get(route('movimientos.index', ['accion' => 'compra']));
        $this->assertCount(15, $primera->viewData('movimientos'));
        $primera->assertSee('accion=compra&amp;page=2', false);

        $segunda = $this->actingAs($this->gerente)->get(route('movimientos.index', ['accion' => 'compra', 'page' => 2]));
        $this->assertCount(1, $segunda->viewData('movimientos'));
        $this->assertSame(16, $segunda->viewData('movimientos')->total());
    }

    // ---------- Registro ----------

    public function test_el_registro_empieza_eligiendo_la_pieza_y_adapta_los_campos(): void
    {
        $this->actingAs($this->gerente)->get(route('movimientos.create'))
            ->assertOk()
            ->assertSee('Radiador · 6 uds.')
            ->assertSee('Continuar');

        $this->actingAs($this->gerente)->get(route('movimientos.create', ['pieza' => $this->pieza->id_pieza, 'accion' => 'venta']))
            ->assertOk()
            ->assertSee('Pieza: Radiador · stock actual 6')
            ->assertSee('data-selector-accion', false)
            ->assertSee('data-acciones="venta baja traslado"', false)
            ->assertSee('data-acciones="entrada compra traslado"', false)
            ->assertSeeInOrder(['Entrada', 'Compra', 'Venta', 'Eliminar pieza', 'Mover pieza'])
            ->assertSee($this->hueco->rutaCompleta().' (6 unidades)')
            ->assertSee('name="clave_envio"', false)
            // El gerente no puede retirar la ficha completa.
            ->assertDontSee('name="retirar_ficha"', false);

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->get(route('movimientos.create', ['pieza' => $this->pieza->id_pieza]))
            ->assertSee('name="retirar_ficha"', false);
    }

    public function test_el_registro_avisa_si_no_hay_piezas(): void
    {
        Movimiento::query()->forceDelete();
        ExistenciaPieza::query()->delete();
        Pieza::query()->forceDelete();

        $this->actingAs($this->gerente)->get(route('movimientos.create'))
            ->assertOk()
            ->assertSee('No hay piezas');
    }

    public function test_gerente_y_administrador_registran_movimientos_y_vuelven_a_la_ficha(): void
    {
        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('movimientos.store', $this->pieza), [
                    'accion' => 'compra', 'cantidad' => 2, 'destino' => $this->hueco->id_etiqueta, 'clave_envio' => (string) Str::uuid(),
                ])
                ->assertRedirect(route('piezas.show', $this->pieza))
                ->assertSessionHas('success');
        }

        $this->assertSame(10, $this->pieza->fresh()->stock);
        $this->assertSame(2, Movimiento::where('tipo_movimiento', 'compra')->count());
    }

    public function test_los_campos_que_no_corresponden_a_la_accion_se_ignoran(): void
    {
        // Una compra con un motivo y un origen (como si el formulario no ocultara esos campos).
        $this->actingAs($this->gerente)->post(route('movimientos.store', $this->pieza), [
            'accion' => 'compra', 'cantidad' => 1, 'destino' => $this->hueco->id_etiqueta,
            'origen' => 'pendiente', 'motivo' => 'No aplica', 'clave_envio' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();

        $compra = Movimiento::where('tipo_movimiento', 'compra')->firstOrFail();
        $this->assertNull($compra->motivo);
        $this->assertNull($compra->id_etiqueta_origen);
    }

    public function test_sin_clave_de_envio_se_pide_volver_a_abrir_el_formulario(): void
    {
        $this->actingAs($this->gerente)
            ->post(route('movimientos.store', $this->pieza), ['accion' => 'compra', 'cantidad' => 1, 'destino' => $this->hueco->id_etiqueta])
            ->assertSessionHasErrors(['clave_envio' => 'El formulario ha caducado. Vuelve a abrirlo e inténtalo de nuevo.']);

        $this->assertSame(6, $this->pieza->fresh()->stock);
    }

    public function test_no_se_registran_movimientos_de_una_pieza_archivada_o_inexistente(): void
    {
        $this->pieza->delete();

        $this->actingAs($this->gerente)->post(route('movimientos.store', $this->pieza), [
            'accion' => 'compra', 'cantidad' => 1, 'destino' => $this->hueco->id_etiqueta, 'clave_envio' => (string) Str::uuid(),
        ])->assertNotFound();
        $this->actingAs($this->gerente)->get(route('movimientos.create', ['pieza' => 99999]))->assertNotFound();
    }
}
