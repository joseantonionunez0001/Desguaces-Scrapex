<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Movimiento;
use App\Models\Pieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class MovimientosWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function categoriaCoherente(string $nombre = 'Motor', string $desguace = 'Desguace Norte', string $modulo = 'Módulo A'): Categoria
    {
        $desguaceCreado = Desguace::factory()->create(['nombre' => $desguace]);
        $moduloCreado = Modulo::factory()->create(['id_desguace' => $desguaceCreado->id_desguace, 'nombre_modulo' => $modulo]);

        return Categoria::factory()->create([
            'nombre' => $nombre,
            'id_desguace' => $desguaceCreado->id_desguace,
            'id_modulo' => $moduloCreado->id_modulo,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function datosValidos(Categoria $categoria): array
    {
        return [
            'tipo_movimiento' => 'Entrada',
            'fecha' => '2026-03-10',
            'cantidad' => '5',
            'id_categoria' => (string) $categoria->categoria_id,
        ];
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('movimientos.index'))->assertRedirect(route('login'));
        $this->get(route('movimientos.create'))->assertRedirect(route('login'));
        $this->post(route('movimientos.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_recibe_403(): void
    {
        $usuario = $this->usuarioConRol();
        $categoria = $this->categoriaCoherente();

        $this->actingAs($usuario)->get(route('movimientos.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('movimientos.create'))->assertForbidden();
        $this->actingAs($usuario)->post(route('movimientos.store'), $this->datosValidos($categoria))->assertForbidden();
        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_todos_los_roles_ven_el_listado_con_datos_reales(): void
    {
        $categoria = $this->categoriaCoherente('Frenos', 'Desguace Levante');
        Movimiento::factory()->create([
            'tipo_movimiento' => 'Salida',
            'fecha' => '2026-02-01',
            'cantidad' => 7,
            'id_categoria' => $categoria->categoria_id,
        ]);

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('movimientos.index'))
                ->assertOk()
                ->assertSee('Salida')
                ->assertSee('01/02/2026')
                ->assertSee('Frenos')
                ->assertSee('Desguace Levante');
        }
    }

    public function test_el_listado_muestra_el_detalle_con_desguace_y_modulo(): void
    {
        $categoria = $this->categoriaCoherente('Frenos', 'Desguace Levante', 'Módulo Z');
        Movimiento::factory()->create(['id_categoria' => $categoria->categoria_id]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('movimientos.index'))
            ->assertOk()
            ->assertSee('Desguace de la categoría')
            ->assertSee('Módulo Z');
    }

    public function test_estado_vacio_sin_movimientos(): void
    {
        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('movimientos.index'))
            ->assertOk()
            ->assertSee('Todavía no hay movimientos')
            ->assertSee('Registrar movimiento');
    }

    public function test_estado_vacio_con_filtros_sin_resultados(): void
    {
        Movimiento::factory()->create(['tipo_movimiento' => 'Entrada']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('movimientos.index', ['q' => 'inexistente']))
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertDontSee('Todavía no hay movimientos');
    }

    public function test_el_buscador_filtra_por_tipo_y_por_categoria(): void
    {
        $motor = $this->categoriaCoherente('Motor');
        $frenos = $this->categoriaCoherente('Frenos');
        Movimiento::factory()->create(['tipo_movimiento' => 'Entrada', 'id_categoria' => $motor->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'Ajuste', 'id_categoria' => $frenos->categoria_id]);

        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get(route('movimientos.index', ['q' => 'Ajuste']))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->total() === 1
                && $movimientos->first()->tipo_movimiento === 'Ajuste');

        $this->actingAs($usuario)->get(route('movimientos.index', ['q' => 'Motor']))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->total() === 1
                && $movimientos->first()->id_categoria === $motor->categoria_id);
    }

    public function test_los_filtros_por_tipo_categoria_y_fechas_se_combinan(): void
    {
        $motor = $this->categoriaCoherente('Motor');
        $frenos = $this->categoriaCoherente('Frenos');
        Movimiento::factory()->create(['tipo_movimiento' => 'Entrada', 'fecha' => '2026-01-10', 'id_categoria' => $motor->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'Entrada', 'fecha' => '2026-03-10', 'id_categoria' => $motor->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'Salida', 'fecha' => '2026-03-12', 'id_categoria' => $motor->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'Entrada', 'fecha' => '2026-03-11', 'id_categoria' => $frenos->categoria_id]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('movimientos.index', [
                'tipo' => 'Entrada',
                'categoria' => $motor->categoria_id,
                'desde' => '2026-02-01',
                'hasta' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->total() === 1
                && $movimientos->first()->fecha->toDateString() === '2026-03-10');
    }

    public function test_el_filtro_de_tipo_ofrece_solo_los_tipos_existentes(): void
    {
        Movimiento::factory()->create(['tipo_movimiento' => 'Traspaso']);
        Movimiento::factory()->create(['tipo_movimiento' => 'Traspaso']);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('movimientos.index'))
            ->assertViewHas('tipos', fn (array $tipos) => $tipos === ['Traspaso' => 'Traspaso']);
    }

    public function test_los_parametros_de_filtro_mal_formados_se_ignoran(): void
    {
        Movimiento::factory()->count(2)->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('movimientos.index', ['desde' => '31/02/2026', 'hasta' => 'no-es-fecha', 'categoria' => 'abc', 'q' => ['x']]))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->total() === 2);
    }

    public function test_el_listado_se_pagina_de_15_en_15_conservando_filtros(): void
    {
        $categoria = $this->categoriaCoherente();
        Movimiento::factory()->count(16)->create(['tipo_movimiento' => 'Entrada', 'id_categoria' => $categoria->categoria_id]);

        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get(route('movimientos.index', ['tipo' => 'Entrada']))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->count() === 15 && $movimientos->total() === 16)
            ->assertSee('tipo=Entrada&amp;page=2', false);

        $this->actingAs($usuario)->get(route('movimientos.index', ['tipo' => 'Entrada', 'page' => 2]))
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->count() === 1);
    }

    public function test_el_boton_de_eliminar_solo_aparece_a_quien_puede_eliminar(): void
    {
        $movimiento = Movimiento::factory()->create();
        $urlDeBorrado = route('movimientos.destroy', $movimiento);

        $this->actingAs($this->usuarioConRol('operario'))->get(route('movimientos.index'))->assertDontSee($urlDeBorrado);
        $this->actingAs($this->usuarioConRol('gerente'))->get(route('movimientos.index'))->assertDontSee($urlDeBorrado);
        $this->actingAs($this->usuarioConRol('administrador'))->get(route('movimientos.index'))->assertSee($urlDeBorrado);
    }

    public function test_operario_gerente_y_administrador_pueden_registrar_movimientos(): void
    {
        $categoria = $this->categoriaCoherente();

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            $this->actingAs($usuario)->get(route('movimientos.create'))
                ->assertOk()
                ->assertSee('Registrar movimiento')
                ->assertSee('Guardar movimiento')
                ->assertSee('Cancelar')
                ->assertSee('Motor · Desguace Norte');

            $this->actingAs($usuario)->post(route('movimientos.store'), $this->datosValidos($categoria))
                ->assertRedirect(route('movimientos.index'))
                ->assertSessionHas('success');
        }

        $this->assertDatabaseCount('movimientos', 3);
        $this->assertDatabaseHas('movimientos', [
            'tipo_movimiento' => 'Entrada',
            'cantidad' => 5,
            'id_categoria' => $categoria->categoria_id,
        ]);
        $this->assertSame('2026-03-10', Movimiento::first()->fecha->toDateString());
    }

    public function test_registrar_un_movimiento_no_modifica_el_stock_de_las_piezas(): void
    {
        $categoria = $this->categoriaCoherente();
        $pieza = Pieza::factory()->create(['stock' => 4]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->post(route('movimientos.store'), $this->datosValidos($categoria))
            ->assertRedirect(route('movimientos.index'));

        $this->assertSame(4, $pieza->fresh()->stock);
    }

    public function test_el_formulario_avisa_si_no_hay_categorias(): void
    {
        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('movimientos.create'))
            ->assertOk()
            ->assertSee('No hay categorías')
            ->assertDontSee('Guardar movimiento');
    }

    public function test_validacion_de_campos_obligatorios(): void
    {
        $this->actingAs($this->usuarioConRol('operario'))
            ->from(route('movimientos.create'))
            ->post(route('movimientos.store'), [])
            ->assertRedirect(route('movimientos.create'))
            ->assertSessionHasErrors(['tipo_movimiento', 'fecha', 'cantidad', 'id_categoria']);

        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_validacion_de_valores_incorrectos(): void
    {
        $categoria = $this->categoriaCoherente();
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->post(route('movimientos.store'), array_merge($this->datosValidos($categoria), [
            'tipo_movimiento' => str_repeat('a', 51),
            'fecha' => 'no-es-fecha',
            'cantidad' => '0',
            'id_categoria' => '999999',
        ]))->assertSessionHasErrors(['tipo_movimiento', 'fecha', 'cantidad', 'id_categoria']);

        $this->actingAs($usuario)->post(route('movimientos.store'), array_merge($this->datosValidos($categoria), ['cantidad' => '2.5']))
            ->assertSessionHasErrors('cantidad');

        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_no_se_puede_registrar_un_movimiento_en_una_categoria_eliminada(): void
    {
        $categoria = $this->categoriaCoherente();
        $categoria->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->post(route('movimientos.store'), $this->datosValidos($categoria))
            ->assertSessionHasErrors('id_categoria');
    }

    public function test_solo_el_administrador_elimina_movimientos(): void
    {
        $movimiento = Movimiento::factory()->create();

        $this->actingAs($this->usuarioConRol())->delete(route('movimientos.destroy', $movimiento))->assertForbidden();
        $this->actingAs($this->usuarioConRol('operario'))->delete(route('movimientos.destroy', $movimiento))->assertForbidden();
        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('movimientos.destroy', $movimiento))->assertForbidden();
        $this->assertNotSoftDeleted($movimiento);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('movimientos.destroy', $movimiento))
            ->assertRedirect(route('movimientos.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($movimiento);
    }

    public function test_eliminar_un_movimiento_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('movimientos.destroy', 999999))
            ->assertNotFound();
    }
}
