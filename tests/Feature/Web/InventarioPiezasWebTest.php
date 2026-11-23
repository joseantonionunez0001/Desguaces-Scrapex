<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\ExistenciaPieza;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Flujo de inventario de una pieza por las pantallas web: alta con Entrada inicial, existencias por hueco,
 * movimientos (Entrada, Compra, Venta, Eliminar pieza y Mover pieza), historial y disponibilidad.
 */
class InventarioPiezasWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private User $gerente;

    private Etiqueta $huecoA;

    private Etiqueta $huecoB;

    private Categoria $categoria;

    private TipoPieza $tipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->gerente = $this->usuarioConRol(RoleSeeder::GERENTE);
        $this->huecoA = $this->hueco('A-01');
        $this->huecoB = $this->hueco('B-07');
        $this->categoria = Categoria::factory()->create(['nombre' => 'Electricidad', 'id_modulo' => null]);
        $this->tipo = TipoPieza::factory()->create(['nombre' => 'Alternador', 'id_categoria' => $this->categoria->categoria_id]);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function alta(array $sobrescribir = []): array
    {
        $modelo = $this->modeloDeVehiculo('Seat', 'Ibiza');

        return array_merge([
            'nombre' => 'Alternador 90A',
            'id_marca' => $modelo->id_marca,
            'id_modelo_vehiculo' => $modelo->id_modelo,
            'id_categoria' => $this->categoria->categoria_id,
            'id_tipo_pieza' => $this->tipo->id_pieza,
            'estado' => 'Buen estado',
            'precio' => '85.50',
            'stock_minimo' => 2,
            'cantidad' => 5,
            'id_etiqueta' => $this->huecoA->id_etiqueta,
            'clave_envio' => (string) Str::uuid(),
        ], $sobrescribir);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function movimiento(Pieza $pieza, array $datos, ?User $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->gerente)
            ->post(route('movimientos.store', $pieza), $datos + ['clave_envio' => (string) Str::uuid()]);
    }

    private function cantidadEn(Pieza $pieza, ?Etiqueta $hueco): int
    {
        return (int) ExistenciaPieza::query()
            ->where('id_pieza', $pieza->id_pieza)
            ->when($hueco, fn ($consulta) => $consulta->where('id_etiqueta', $hueco->id_etiqueta), fn ($consulta) => $consulta->whereNull('id_etiqueta'))
            ->value('cantidad');
    }

    private function registrarPieza(): Pieza
    {
        $this->actingAs($this->gerente)->post(route('piezas.store'), $this->alta())->assertRedirect();

        return Pieza::query()->latest('id_pieza')->firstOrFail();
    }

    // ---------- Verificación del flujo completo ----------

    public function test_flujo_completo_entrada_traslado_venta_stock_bajo_e_historial(): void
    {
        // 1. Alta con 5 unidades en el hueco A y stock mínimo 2: una sola Entrada.
        $pieza = $this->registrarPieza();

        $this->assertSame(5, $pieza->stock);
        $this->assertSame(5, $this->cantidadEn($pieza, $this->huecoA));
        $this->assertSame(1, Movimiento::where('id_pieza', $pieza->id_pieza)->count());
        $this->assertDatabaseHas('movimientos', [
            'id_pieza' => $pieza->id_pieza,
            'tipo_movimiento' => Movimiento::ENTRADA,
            'cantidad' => 5,
            'id_usuario' => $this->gerente->id,
            'id_etiqueta_destino' => $this->huecoA->id_etiqueta,
            'saldo_anterior' => 0,
            'saldo_posterior' => 5,
            'id_categoria' => $this->categoria->categoria_id,
        ]);
        $this->assertSame($this->gerente->id, $pieza->id_usuario);

        // 2. Mover 2 unidades al hueco B: el total sigue siendo 5.
        $this->movimiento($pieza, [
            'accion' => 'traslado', 'cantidad' => 2, 'origen' => (string) $this->huecoA->id_etiqueta, 'destino' => $this->huecoB->id_etiqueta,
        ])->assertRedirect(route('piezas.show', $pieza))->assertSessionHas('success');

        $pieza->refresh();
        $this->assertSame(5, $pieza->stock);
        $this->assertSame(3, $this->cantidadEn($pieza, $this->huecoA));
        $this->assertSame(2, $this->cantidadEn($pieza, $this->huecoB));

        // 3. Vender las 3 unidades restantes del hueco A: quedan 2 en el hueco B y aparece «Stock bajo».
        $this->movimiento($pieza, [
            'accion' => 'venta', 'cantidad' => 3, 'origen' => (string) $this->huecoA->id_etiqueta,
        ])->assertSessionHas('success');

        $pieza->refresh();
        $this->assertSame(2, $pieza->stock);
        $this->assertSame(0, $this->cantidadEn($pieza, $this->huecoA));
        $this->assertSame(2, $this->cantidadEn($pieza, $this->huecoB));
        $this->assertSame(Pieza::STOCK_BAJO, $pieza->disponibilidad());

        // 4. El historial refleja las tres operaciones con sus saldos.
        $historial = Movimiento::where('id_pieza', $pieza->id_pieza)->orderBy('id_movimiento')->get();
        $this->assertSame([Movimiento::ENTRADA, Movimiento::TRASLADO, Movimiento::VENTA], $historial->pluck('tipo_movimiento')->all());
        $this->assertSame([[0, 5], [5, 5], [5, 2]], $historial->map(fn ($m) => [$m->saldo_anterior, $m->saldo_posterior])->all());

        $ficha = $this->actingAs($this->gerente)->get(route('piezas.show', $pieza))->assertOk();
        $ficha->assertSee('Stock bajo')
            ->assertSee('Entrada')
            ->assertSee('Mover pieza')
            ->assertSee('Venta')
            ->assertSee($this->huecoB->rutaCompleta())
            ->assertSee($this->gerente->name)
            ->assertSeeInOrder(['Existencias por ubicación', 'Etiqueta B-07', 'Total', '2']);
        $this->assertSame(3, $ficha->viewData('historial')->total());

        // El listado también avisa.
        $this->actingAs($this->gerente)->get(route('piezas.index'))->assertSee('Stock bajo');
    }

    public function test_se_rechaza_una_salida_sin_existencias_suficientes_en_el_hueco(): void
    {
        $pieza = $this->registrarPieza();
        $this->movimiento($pieza, [
            'accion' => 'traslado', 'cantidad' => 2, 'origen' => (string) $this->huecoA->id_etiqueta, 'destino' => $this->huecoB->id_etiqueta,
        ]);

        // El hueco B tiene 2: no se pueden vender 3, aunque el total de la pieza sea 5.
        $this->movimiento($pieza, ['accion' => 'venta', 'cantidad' => 3, 'origen' => (string) $this->huecoB->id_etiqueta])
            ->assertSessionHasErrors(['cantidad' => 'Solo hay 2 unidades en el hueco de origen.']);

        // Un hueco sin unidades de la pieza.
        $vacio = $this->hueco('C-99');
        $this->movimiento($pieza, ['accion' => 'baja', 'cantidad' => 1, 'origen' => (string) $vacio->id_etiqueta, 'motivo' => 'Rota'])
            ->assertSessionHasErrors(['cantidad' => 'No hay unidades de esta pieza en el hueco de origen.']);

        $pieza->refresh();
        $this->assertSame(5, $pieza->stock);
        $this->assertSame(3, $this->cantidadEn($pieza, $this->huecoA));
        $this->assertSame(2, $this->cantidadEn($pieza, $this->huecoB));
        $this->assertSame(2, Movimiento::where('id_pieza', $pieza->id_pieza)->count());
    }

    public function test_un_doble_envio_no_duplica_el_alta_ni_el_movimiento(): void
    {
        $alta = $this->alta();
        $this->actingAs($this->gerente)->post(route('piezas.store'), $alta)->assertRedirect();
        $this->actingAs($this->gerente)->post(route('piezas.store'), $alta)->assertRedirect();

        $this->assertSame(1, Pieza::count());
        $pieza = Pieza::firstOrFail();
        $this->assertSame(5, $pieza->stock);
        $this->assertSame(1, Movimiento::where('tipo_movimiento', Movimiento::ENTRADA)->count());

        $venta = ['accion' => 'venta', 'cantidad' => 1, 'origen' => (string) $this->huecoA->id_etiqueta, 'clave_envio' => (string) Str::uuid()];
        $this->actingAs($this->gerente)->post(route('movimientos.store', $pieza), $venta)->assertRedirect();
        $this->actingAs($this->gerente)->post(route('movimientos.store', $pieza), $venta)->assertRedirect();

        $this->assertSame(4, $pieza->fresh()->stock);
        $this->assertSame(1, Movimiento::where('tipo_movimiento', Movimiento::VENTA)->count());
    }

    public function test_se_rechazan_cantidades_invalidas_y_traslados_al_mismo_hueco(): void
    {
        $pieza = $this->registrarPieza();
        $origen = (string) $this->huecoA->id_etiqueta;

        foreach ([0, -3, 'abc', 1.5, ''] as $cantidad) {
            $this->movimiento($pieza, ['accion' => 'venta', 'cantidad' => $cantidad, 'origen' => $origen])->assertSessionHasErrors('cantidad');
        }

        $this->movimiento($pieza, ['accion' => 'traslado', 'cantidad' => 1, 'origen' => $origen, 'destino' => $this->huecoA->id_etiqueta])
            ->assertSessionHasErrors('destino');
        $this->movimiento($pieza, ['accion' => 'inventada', 'cantidad' => 1])->assertSessionHasErrors('accion');
        $this->movimiento($pieza, ['accion' => 'compra', 'cantidad' => 1])->assertSessionHasErrors('destino');
        $this->movimiento($pieza, ['accion' => 'baja', 'cantidad' => 1, 'origen' => $origen])->assertSessionHasErrors('motivo');

        $this->assertSame(5, $pieza->fresh()->stock);
        $this->assertSame(1, Movimiento::where('id_pieza', $pieza->id_pieza)->count());
    }

    public function test_compra_suma_en_el_destino_y_baja_parcial_descuenta_con_motivo(): void
    {
        $pieza = $this->registrarPieza();

        $this->movimiento($pieza, ['accion' => 'compra', 'cantidad' => 4, 'destino' => $this->huecoB->id_etiqueta])->assertSessionHas('success');
        $this->movimiento($pieza, ['accion' => 'entrada', 'cantidad' => 1, 'destino' => $this->huecoB->id_etiqueta])->assertSessionHas('success');
        $this->movimiento($pieza, [
            'accion' => 'baja', 'cantidad' => 2, 'origen' => (string) $this->huecoA->id_etiqueta, 'motivo' => 'Carcasa rota',
        ])->assertSessionHas('success');

        $pieza->refresh();
        $this->assertSame(8, $pieza->stock);
        $this->assertSame(3, $this->cantidadEn($pieza, $this->huecoA));
        $this->assertSame(5, $this->cantidadEn($pieza, $this->huecoB));
        $this->assertFalse($pieza->trashed());
        $this->assertDatabaseHas('movimientos', ['id_pieza' => $pieza->id_pieza, 'tipo_movimiento' => 'baja', 'cantidad' => 2, 'motivo' => 'Carcasa rota']);

        $this->actingAs($this->gerente)->get(route('piezas.show', $pieza))->assertSee('Carcasa rota')->assertSee('Eliminar pieza');
    }

    public function test_retirar_la_ficha_completa_la_archiva_con_su_historial(): void
    {
        $pieza = $this->registrarPieza();
        $this->movimiento($pieza, [
            'accion' => 'traslado', 'cantidad' => 2, 'origen' => (string) $this->huecoA->id_etiqueta, 'destino' => $this->huecoB->id_etiqueta,
        ]);

        // El gerente no puede retirar fichas (no tiene piezas.eliminar).
        $this->movimiento($pieza, ['accion' => 'baja', 'motivo' => 'Fin de stock', 'retirar_ficha' => '1'])->assertForbidden();

        $administrador = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->movimiento($pieza, ['accion' => 'baja', 'motivo' => 'Retirada del catálogo', 'retirar_ficha' => '1'], $administrador)
            ->assertRedirect(route('movimientos.index'));

        $this->assertSoftDeleted('piezas', ['id_pieza' => $pieza->id_pieza]);
        $archivada = Pieza::withTrashed()->findOrFail($pieza->id_pieza);
        $this->assertSame(0, $archivada->stock);
        $this->assertSame(0, (int) ExistenciaPieza::where('id_pieza', $pieza->id_pieza)->sum('cantidad'));

        // Una baja por hueco (3 en A y 2 en B) y el historial completo se conserva.
        $bajas = Movimiento::where('id_pieza', $pieza->id_pieza)->where('tipo_movimiento', 'baja')->orderBy('id_movimiento')->get();
        $this->assertSame([3, 2], $bajas->pluck('cantidad')->all());
        $this->assertSame(4, Movimiento::where('id_pieza', $pieza->id_pieza)->count());

        $this->actingAs($administrador)->get(route('movimientos.index'))
            ->assertSee('Retirada del catálogo')
            ->assertSee('(archivada)');
        $this->actingAs($administrador)->get(route('piezas.show', $pieza))->assertNotFound();
    }

    public function test_el_stock_antiguo_pendiente_de_ubicar_se_conserva_y_se_puede_colocar(): void
    {
        // Pieza anterior a esta versión: stock sin hueco (la factory lo deja como «Pendiente de ubicar»).
        $antigua = Pieza::factory()->create(['id_tipo_pieza' => $this->tipo->id_pieza, 'stock' => 7, 'stock_minimo' => 1]);

        $this->assertSame(7, $this->cantidadEn($antigua, null));
        $this->actingAs($this->gerente)->get(route('piezas.show', $antigua))
            ->assertSee('Pendiente de ubicar')
            ->assertSeeInOrder(['Total', '7']);

        // El equipo la coloca con «Mover pieza»; no se asigna ningún hueco por suposición.
        $this->movimiento($antigua, ['accion' => 'traslado', 'cantidad' => 4, 'origen' => 'pendiente', 'destino' => $this->huecoA->id_etiqueta])
            ->assertSessionHas('success');

        $antigua->refresh();
        $this->assertSame(7, $antigua->stock);
        $this->assertSame(3, $this->cantidadEn($antigua, null));
        $this->assertSame(4, $this->cantidadEn($antigua, $this->huecoA));
        $this->assertDatabaseHas('movimientos', ['id_pieza' => $antigua->id_pieza, 'tipo_movimiento' => 'traslado', 'id_etiqueta_origen' => null]);
    }

    public function test_la_migracion_conserva_el_saldo_de_los_registros_antiguos(): void
    {
        // Piezas con stock y sin existencias, como antes de la migración (también una archivada).
        $sinExistencias = fn (int $stock) => tap(
            Pieza::factory()->create(['id_tipo_pieza' => $this->tipo->id_pieza, 'stock' => $stock]),
            fn (Pieza $pieza) => ExistenciaPieza::where('id_pieza', $pieza->id_pieza)->delete(),
        );
        $una = $sinExistencias(9);
        $otra = $sinExistencias(4);
        $otra->delete();
        $agotada = $sinExistencias(0);

        $migracion = require database_path('migrations/2026_11_23_191645_create_existencias_pieza_table.php');
        $migracion->registrarStockAntiguoComoPendiente();
        // Es idempotente: ejecutarla otra vez no duplica nada.
        $migracion->registrarStockAntiguoComoPendiente();

        $this->assertSame(9, (int) DB::table('existencias_pieza')->where('id_pieza', $una->id_pieza)->whereNull('id_etiqueta')->sum('cantidad'));
        $this->assertSame(4, (int) DB::table('existencias_pieza')->where('id_pieza', $otra->id_pieza)->whereNull('id_etiqueta')->sum('cantidad'));
        $this->assertSame(0, DB::table('existencias_pieza')->where('id_pieza', $agotada->id_pieza)->count());
        $this->assertSame(1, DB::table('existencias_pieza')->where('id_pieza', $una->id_pieza)->count());
        $this->assertSame(9, $una->fresh()->stock);
    }

    public function test_los_huecos_de_otra_sede_se_rechazan_en_el_alta_y_en_los_movimientos(): void
    {
        $otraSede = $this->crearSede();
        $huecoAjeno = $this->hueco('Z-01', $otraSede, 'Módulo ajeno');

        $this->actingAs($this->gerente)->post(route('piezas.store'), $this->alta(['id_etiqueta' => $huecoAjeno->id_etiqueta]))
            ->assertSessionHasErrors('id_etiqueta');
        $this->assertSame(0, Pieza::count());

        $pieza = $this->registrarPieza();
        $this->movimiento($pieza, ['accion' => 'compra', 'cantidad' => 1, 'destino' => $huecoAjeno->id_etiqueta])->assertSessionHasErrors('destino');
        $this->movimiento($pieza, [
            'accion' => 'traslado', 'cantidad' => 1, 'origen' => (string) $this->huecoA->id_etiqueta, 'destino' => $huecoAjeno->id_etiqueta,
        ])->assertSessionHasErrors('destino');

        // Origen de otra sede: no hay existencias de la pieza ahí y, aunque las hubiera, el servicio lo rechaza.
        $this->movimiento($pieza, ['accion' => 'venta', 'cantidad' => 1, 'origen' => (string) $huecoAjeno->id_etiqueta])->assertSessionHasErrors();

        $this->assertSame(5, $pieza->fresh()->stock);
        $this->assertSame(0, ExistenciaPieza::withoutGlobalScopes()->where('id_etiqueta', $huecoAjeno->id_etiqueta)->count());
    }

    public function test_el_operario_no_registra_movimientos_ni_piezas(): void
    {
        $pieza = $this->registrarPieza();
        $operario = $this->usuarioConRol(RoleSeeder::OPERARIO);

        $this->actingAs($operario)->get(route('piezas.show', $pieza))->assertOk()->assertDontSee('Registrar movimiento');
        $this->actingAs($operario)->get(route('piezas.create'))->assertForbidden();
        $this->actingAs($operario)->post(route('piezas.store'), $this->alta())->assertForbidden();
        $this->movimiento($pieza, ['accion' => 'venta', 'cantidad' => 1, 'origen' => (string) $this->huecoA->id_etiqueta], $operario)->assertForbidden();

        $this->assertSame(5, $pieza->fresh()->stock);
    }
}
