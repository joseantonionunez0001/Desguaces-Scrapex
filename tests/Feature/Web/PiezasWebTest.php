<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\ModeloVehiculo;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\TipoPieza;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantallas web de piezas: listado con filtros combinables, ficha, alta (con Entrada inicial) y edición.
 * El flujo de movimientos y existencias se prueba en InventarioPiezasWebTest.
 */
class PiezasWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Categoria $electricidad;

    private Categoria $carroceria;

    private TipoPieza $alternador;

    private TipoPieza $puerta;

    private ModeloVehiculo $ibiza;

    private ModeloVehiculo $leon;

    private ModeloVehiculo $clio;

    private Etiqueta $hueco;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->electricidad = Categoria::factory()->create(['nombre' => 'Electricidad', 'id_modulo' => null]);
        $this->carroceria = Categoria::factory()->create(['nombre' => 'Carrocería', 'id_modulo' => null]);
        $this->alternador = TipoPieza::factory()->create(['nombre' => 'Alternador', 'fabricante' => 'Bosch', 'id_categoria' => $this->electricidad->categoria_id]);
        $this->puerta = TipoPieza::factory()->create(['nombre' => 'Puerta', 'fabricante' => null, 'id_categoria' => $this->carroceria->categoria_id]);
        $this->ibiza = $this->modeloDeVehiculo('Seat', 'Ibiza');
        $this->leon = $this->modeloDeVehiculo('Seat', 'León');
        $this->clio = $this->modeloDeVehiculo('Renault', 'Clio');
        $this->hueco = $this->hueco('A-01');
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function pieza(array $atributos = []): Pieza
    {
        return Pieza::factory()->create($atributos + [
            'id_tipo_pieza' => $this->alternador->id_pieza,
            'id_modelo_vehiculo' => $this->ibiza->id_modelo,
            'estado' => 'Buen estado',
        ]);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function alta(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre' => 'Alternador 90A',
            'id_marca' => $this->ibiza->id_marca,
            'id_modelo_vehiculo' => $this->ibiza->id_modelo,
            'id_categoria' => $this->electricidad->categoria_id,
            'id_tipo_pieza' => $this->alternador->id_pieza,
            'estado' => 'Buen estado',
            'precio' => '85.50',
            'stock_minimo' => 2,
            'cantidad' => 5,
            'id_etiqueta' => $this->hueco->id_etiqueta,
            'clave_envio' => (string) Str::uuid(),
        ], $sobrescribir);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $pieza = $this->pieza();

        $this->get(route('piezas.index'))->assertRedirect(route('login'));
        $this->get(route('piezas.show', $pieza))->assertRedirect(route('login'));
        $this->get(route('piezas.create'))->assertRedirect(route('login'));
        $this->post(route('piezas.store'), [])->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403_en_todas_las_rutas(): void
    {
        $pieza = $this->pieza();
        $this->actingAs($this->usuarioConRol(null));

        $this->get(route('piezas.index'))->assertForbidden();
        $this->get(route('piezas.show', $pieza))->assertForbidden();
        $this->get(route('piezas.create'))->assertForbidden();
        $this->post(route('piezas.store'), $this->alta())->assertForbidden();
        $this->get(route('piezas.edit', $pieza))->assertForbidden();
        $this->put(route('piezas.update', $pieza), [])->assertForbidden();
    }

    public function test_ya_no_hay_borrado_directo_de_piezas(): void
    {
        $pieza = $this->pieza();

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->delete('/piezas/'.$pieza->id_pieza)
            ->assertStatus(405);

        $this->assertNotSoftDeleted($pieza);
    }

    // ---------- Listado ----------

    public function test_cada_rol_autorizado_ve_el_listado_con_datos_reales(): void
    {
        $this->pieza(['nombre' => 'Alternador Bosch 90A', 'precio' => 120.5, 'stock' => 4, 'stock_minimo' => 1]);

        foreach ([RoleSeeder::ADMIN_DESGUACE, 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('piezas.index'))
                ->assertOk()
                ->assertSeeInOrder(['Alternador Bosch 90A', 'Seat Ibiza', 'Electricidad', 'Alternador', '120,50 €', '4', '1', 'Buen estado', 'Con stock']);
        }
    }

    public function test_el_listado_vacio_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.index'))
            ->assertOk()
            ->assertSee('No hay piezas registradas');
    }

    public function test_el_listado_ordena_las_mas_recientes_primero_y_no_muestra_archivadas(): void
    {
        $this->pieza(['nombre' => 'Primera pieza']);
        $this->pieza(['nombre' => 'Segunda pieza']);
        $this->pieza(['nombre' => 'Pieza archivada'])->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index'))
            ->assertSeeInOrder(['Segunda pieza', 'Primera pieza'])
            ->assertDontSee('Pieza archivada');
    }

    public function test_el_listado_pagina_de_15_en_15_y_conserva_los_filtros(): void
    {
        foreach (range(1, 16) as $numero) {
            $this->pieza(['nombre' => "Pieza {$numero}", 'stock' => 5, 'stock_minimo' => 1]);
        }
        $usuario = $this->usuarioConRol('operario');
        $filtros = ['marca' => $this->ibiza->id_marca, 'disponibilidad' => Pieza::CON_STOCK];

        $primera = $this->actingAs($usuario)->get(route('piezas.index', $filtros));
        $this->assertCount(15, $primera->viewData('piezas'));
        $primera->assertSee('marca='.$this->ibiza->id_marca.'&amp;disponibilidad=con_stock&amp;page=2', false);

        $segunda = $this->actingAs($usuario)->get(route('piezas.index', $filtros + ['page' => 2]));
        $this->assertCount(1, $segunda->viewData('piezas'));
    }

    public function test_los_filtros_se_combinan(): void
    {
        $buscada = $this->pieza(['nombre' => 'Alternador Ibiza', 'stock' => 1, 'stock_minimo' => 2, 'estado' => 'Buen estado']);
        $this->pieza(['nombre' => 'Alternador León', 'id_modelo_vehiculo' => $this->leon->id_modelo, 'stock' => 1, 'stock_minimo' => 2]);
        $this->pieza(['nombre' => 'Alternador Clio', 'id_modelo_vehiculo' => $this->clio->id_modelo, 'stock' => 1, 'stock_minimo' => 2]);
        $this->pieza(['nombre' => 'Alternador dañado', 'stock' => 1, 'stock_minimo' => 2, 'estado' => 'Dañada']);
        $this->pieza(['nombre' => 'Alternador con stock', 'stock' => 9, 'stock_minimo' => 2]);
        $this->pieza(['nombre' => 'Puerta Ibiza', 'id_tipo_pieza' => $this->puerta->id_pieza, 'stock' => 1, 'stock_minimo' => 2]);
        $usuario = $this->usuarioConRol('gerente');

        $ids = fn (array $filtros) => $this->actingAs($usuario)->get(route('piezas.index', $filtros))
            ->assertOk()->viewData('piezas')->pluck('id_pieza')->all();

        $this->assertSame([$buscada->id_pieza], $ids([
            'marca' => $this->ibiza->id_marca,
            'modelo' => $this->ibiza->id_modelo,
            'categoria' => $this->electricidad->categoria_id,
            'tipo' => $this->alternador->id_pieza,
            'estado' => 'Buen estado',
            'disponibilidad' => Pieza::STOCK_BAJO,
        ]));

        // Cada filtro por separado.
        $this->assertCount(5, $ids(['marca' => $this->ibiza->id_marca]));
        $this->assertCount(4, $ids(['modelo' => $this->ibiza->id_modelo]));
        $this->assertCount(1, $ids(['categoria' => $this->carroceria->categoria_id]));
        $this->assertCount(1, $ids(['estado' => 'Dañada']));
        $this->assertCount(1, $ids(['disponibilidad' => Pieza::CON_STOCK]));
        $this->assertCount(1, $ids(['q' => 'Clio']));
    }

    public function test_la_disponibilidad_se_calcula_con_las_existencias(): void
    {
        $conStock = $this->pieza(['nombre' => 'Con unidades', 'stock' => 5, 'stock_minimo' => 2]);
        $bajo = $this->pieza(['nombre' => 'En el mínimo', 'stock' => 2, 'stock_minimo' => 2]);
        $agotada = $this->pieza(['nombre' => 'Sin unidades', 'stock' => 0, 'stock_minimo' => 0]);
        $usuario = $this->usuarioConRol('operario');

        $this->assertSame(Pieza::CON_STOCK, $conStock->disponibilidad());
        $this->assertSame(Pieza::STOCK_BAJO, $bajo->disponibilidad());
        // Stock cero con mínimo cero: agotada, no «stock bajo».
        $this->assertSame(Pieza::AGOTADA, $agotada->disponibilidad());

        foreach ([Pieza::CON_STOCK => $conStock, Pieza::STOCK_BAJO => $bajo, Pieza::AGOTADA => $agotada] as $filtro => $pieza) {
            $this->assertSame([$pieza->id_pieza], $this->actingAs($usuario)->get(route('piezas.index', ['disponibilidad' => $filtro]))
                ->viewData('piezas')->pluck('id_pieza')->all());
        }

        $this->actingAs($usuario)->get(route('piezas.show', $bajo))->assertSee('Stock bajo')->assertSee('el mínimo es 2');
        $this->actingAs($usuario)->get(route('piezas.show', $agotada))->assertSee('Agotada');
    }

    public function test_el_listado_sin_coincidencias_ofrece_limpiar_filtros_y_los_filtros_invalidos_se_ignoran(): void
    {
        $this->pieza(['nombre' => 'Faro']);
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get(route('piezas.index', ['q' => 'inexistente']))
            ->assertSee('Sin resultados')
            ->assertSee('Limpiar filtros');

        $this->actingAs($usuario)->get(route('piezas.index', ['marca' => 'abc', 'disponibilidad' => 'otra', 'tipo' => '-1']))
            ->assertOk()
            ->assertSee('Faro');
    }

    public function test_al_elegir_una_marca_o_una_categoria_se_ofrecen_sus_modelos_y_tipos(): void
    {
        $pagina = $this->actingAs($this->usuarioConRol('gerente'))->get(route('piezas.index'))->assertOk();

        // Cada modelo y cada tipo lleva su padre: el desplegable dependiente filtra con él.
        $pagina->assertSee('data-depende-de="marca"', false)->assertSee('data-depende-de="categoria"', false);
        $html = $pagina->getContent();
        $opcion = fn (int $valor, int $padre): string => '/<option value="'.$valor.'"\s+data-padre="'.$padre.'"/';

        $this->assertMatchesRegularExpression($opcion($this->leon->id_modelo, $this->ibiza->id_marca), $html);
        $this->assertMatchesRegularExpression($opcion($this->clio->id_modelo, $this->clio->id_marca), $html);
        $this->assertMatchesRegularExpression($opcion($this->puerta->id_pieza, $this->carroceria->categoria_id), $html);
    }

    public function test_los_botones_del_listado_dependen_del_permiso(): void
    {
        $pieza = $this->pieza();

        $this->actingAs($this->usuarioConRol('operario'))->get(route('piezas.index'))
            ->assertDontSee(route('piezas.create'))
            ->assertDontSee(route('piezas.edit', $pieza));

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('piezas.index'))
            ->assertSee(route('piezas.create'))
            ->assertSee(route('piezas.edit', $pieza))
            ->assertSee(route('movimientos.create', ['pieza' => $pieza->id_pieza]), false);
    }

    // ---------- Ficha ----------

    public function test_la_ficha_muestra_vehiculo_clasificacion_existencias_y_usuario(): void
    {
        $gerente = $this->usuarioConRol('gerente');
        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta(['cantidad' => 3]));
        $pieza = Pieza::firstOrFail();

        $this->actingAs($this->usuarioConRol('operario'))->get(route('piezas.show', $pieza))
            ->assertOk()
            ->assertSee('Seat Ibiza')
            ->assertSee('Electricidad')
            ->assertSee('Alternador')
            ->assertSee('Bosch')
            ->assertSee('Buen estado')
            ->assertSee($gerente->name)
            ->assertSee($this->hueco->rutaCompleta())
            ->assertSee('Historial de movimientos');
    }

    public function test_la_ficha_de_una_pieza_archivada_o_inexistente_devuelve_404(): void
    {
        $archivada = $this->pieza();
        $archivada->delete();
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get(route('piezas.show', $archivada))->assertNotFound();
        $this->actingAs($usuario)->get('/piezas/99999')->assertNotFound();
    }

    // ---------- Alta ----------

    public function test_el_formulario_de_alta_pide_los_datos_de_la_pieza_y_su_entrada(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))->get(route('piezas.create'))
            ->assertOk()
            ->assertSeeInOrder([
                'Nombre', 'Marca del vehículo', 'Modelo del vehículo', 'Categoría', 'Tipo de pieza',
                'Estado físico', 'Precio', 'Stock mínimo', 'Cantidad que ha entrado', 'Ubicación inicial',
            ])
            ->assertSee('name="clave_envio"', false)
            ->assertDontSee('name="id_usuario"', false)
            ->assertDontSee('name="stock"', false)
            ->assertSee($this->hueco->rutaCompleta());
    }

    public function test_un_gerente_registra_una_pieza_a_su_nombre_con_su_entrada_inicial(): void
    {
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta())
            ->assertRedirect()
            ->assertSessionHas('success');

        $pieza = Pieza::firstOrFail();
        $this->assertSame('Alternador 90A', $pieza->nombre);
        $this->assertSame($gerente->id, $pieza->id_usuario);
        $this->assertSame($this->ibiza->id_modelo, $pieza->id_modelo_vehiculo);
        $this->assertSame(5, $pieza->stock);
        $this->assertSame(1, Movimiento::where('id_pieza', $pieza->id_pieza)->where('tipo_movimiento', 'entrada')->count());
    }

    public function test_el_modelo_debe_ser_de_la_marca_y_el_tipo_de_la_categoria(): void
    {
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta(['id_modelo_vehiculo' => $this->clio->id_modelo]))
            ->assertSessionHasErrors(['id_modelo_vehiculo' => 'El modelo elegido no corresponde a la marca seleccionada.']);

        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta(['id_tipo_pieza' => $this->puerta->id_pieza]))
            ->assertSessionHasErrors(['id_tipo_pieza' => 'El tipo de pieza elegido no pertenece a la categoría seleccionada.']);

        $this->assertSame(0, Pieza::count());
        $this->assertSame(0, Movimiento::count());
    }

    public function test_el_alta_valida_los_campos_obligatorios_y_los_valores(): void
    {
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->post(route('piezas.store'), [])
            ->assertSessionHasErrors([
                'nombre', 'id_marca', 'id_modelo_vehiculo', 'id_categoria', 'id_tipo_pieza', 'estado',
                'precio', 'stock_minimo', 'cantidad', 'id_etiqueta', 'clave_envio',
            ]);

        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta([
            'precio' => -1,
            'stock_minimo' => 1.5,
            'cantidad' => 0,
            'estado' => 'Inventado',
            'id_tipo_pieza' => 99999,
            'id_etiqueta' => 99999,
        ]))->assertSessionHasErrors(['precio', 'stock_minimo', 'cantidad', 'estado', 'id_tipo_pieza', 'id_etiqueta']);

        // El stock y el usuario no se aceptan desde el formulario.
        $this->actingAs($gerente)->post(route('piezas.store'), $this->alta(['stock' => 50, 'id_usuario' => 1]))
            ->assertSessionHasErrors(['stock', 'id_usuario']);

        $this->assertSame(0, Pieza::count());
    }

    public function test_un_operario_no_puede_registrar_piezas(): void
    {
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->get(route('piezas.create'))->assertForbidden();
        $this->actingAs($operario)->post(route('piezas.store'), $this->alta())->assertForbidden();

        $this->assertSame(0, Pieza::count());
    }

    // ---------- Edición ----------

    public function test_el_formulario_de_edicion_muestra_los_datos_actuales_sin_editar_el_stock(): void
    {
        $pieza = $this->pieza(['nombre' => 'Faro antiguo', 'stock' => 6]);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('piezas.edit', $pieza))
            ->assertOk()
            ->assertSee('value="Faro antiguo"', false)
            ->assertSee('6 unidades')
            ->assertDontSee('name="stock"', false)
            ->assertDontSee('name="cantidad"', false);
    }

    public function test_un_gerente_edita_una_pieza_y_se_conservan_el_stock_y_quien_la_registro(): void
    {
        $pieza = $this->pieza(['stock' => 6]);
        $registrador = $pieza->id_usuario;

        $this->actingAs($this->usuarioConRol('gerente'))->put(route('piezas.update', $pieza), [
            'nombre' => 'Puerta delantera',
            'id_marca' => $this->clio->id_marca,
            'id_modelo_vehiculo' => $this->clio->id_modelo,
            'id_categoria' => $this->carroceria->categoria_id,
            'id_tipo_pieza' => $this->puerta->id_pieza,
            'estado' => 'Pendiente de revisar',
            'precio' => 40,
            'stock_minimo' => 3,
        ])->assertRedirect(route('piezas.show', $pieza))->assertSessionHas('success');

        $pieza->refresh();
        $this->assertSame('Puerta delantera', $pieza->nombre);
        $this->assertSame($this->clio->id_modelo, $pieza->id_modelo_vehiculo);
        $this->assertSame('Pendiente de revisar', $pieza->estado);
        $this->assertSame(6, $pieza->stock);
        $this->assertSame($registrador, $pieza->id_usuario);
    }

    public function test_la_edicion_conserva_un_estado_antiguo_y_rechaza_el_stock(): void
    {
        $pieza = $this->pieza(['estado' => 'Reacondicionada', 'stock' => 4]);
        $datos = [
            'nombre' => 'Faro',
            'id_marca' => $this->ibiza->id_marca,
            'id_modelo_vehiculo' => $this->ibiza->id_modelo,
            'id_categoria' => $this->electricidad->categoria_id,
            'id_tipo_pieza' => $this->alternador->id_pieza,
            'estado' => 'Reacondicionada',
            'precio' => 10,
            'stock_minimo' => 1,
        ];
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->put(route('piezas.update', $pieza), $datos)->assertSessionHasNoErrors();
        $this->actingAs($gerente)->put(route('piezas.update', $pieza), ['stock' => 99] + $datos)->assertSessionHasErrors('stock');

        $this->assertSame(4, $pieza->fresh()->stock);
        $this->assertSame('Reacondicionada', $pieza->fresh()->estado);
    }

    public function test_un_operario_no_puede_editar_piezas(): void
    {
        $pieza = $this->pieza(['nombre' => 'Original']);
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->get(route('piezas.edit', $pieza))->assertForbidden();
        $this->actingAs($operario)->put(route('piezas.update', $pieza), ['nombre' => 'Cambiado'])->assertForbidden();

        $this->assertSame('Original', $pieza->fresh()->nombre);
    }
}
