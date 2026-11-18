<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantallas web de piezas: listado, ficha y formularios de alta, edición y baja.
 */
class PiezasWebTest extends TestCase
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
    private function datosPieza(TipoPieza $tipo, array $sobrescribir = []): array
    {
        return array_merge([
            'nombre' => 'Alternador Bosch',
            'precio' => '120.50',
            'estado' => 'Usada',
            'stock' => 4,
            'stock_minimo' => 2,
            'id_tipo_pieza' => $tipo->id_pieza,
        ], $sobrescribir);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $pieza = Pieza::factory()->create();

        $this->get(route('piezas.index'))->assertRedirect(route('login'));
        $this->get(route('piezas.show', $pieza))->assertRedirect(route('login'));
        $this->get(route('piezas.create'))->assertRedirect(route('login'));
        $this->get(route('piezas.edit', $pieza))->assertRedirect(route('login'));
        $this->post(route('piezas.store'), [])->assertRedirect(route('login'));
        $this->delete(route('piezas.destroy', $pieza))->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403_en_todas_las_rutas(): void
    {
        $pieza = Pieza::factory()->create();
        $this->actingAs($this->usuarioConRol(null));

        $this->get(route('piezas.index'))->assertForbidden();
        $this->get(route('piezas.show', $pieza))->assertForbidden();
        $this->get(route('piezas.create'))->assertForbidden();
        $this->post(route('piezas.store'), [])->assertForbidden();
        $this->get(route('piezas.edit', $pieza))->assertForbidden();
        $this->put(route('piezas.update', $pieza), [])->assertForbidden();
        $this->delete(route('piezas.destroy', $pieza))->assertForbidden();
    }

    // ---------- Listado ----------

    public function test_cada_rol_autorizado_ve_el_listado_con_datos_reales(): void
    {
        $tipo = TipoPieza::factory()->create(['nombre' => 'Tipo Alternador']);
        Pieza::factory()->create([
            'nombre' => 'Alternador Valeo',
            'id_tipo_pieza' => $tipo->id_pieza,
            'precio' => 1234.5,
            'stock' => 7,
            'stock_minimo' => 3,
            'estado' => 'Reacondicionada',
        ]);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('piezas.index'))
                ->assertOk()
                ->assertSeeInOrder(['Alternador Valeo', 'Tipo Alternador', '1.234,50 €', '7', '3', 'Reacondicionada', 'Disponible']);
        }
    }

    public function test_el_listado_vacio_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.index'))
            ->assertOk()
            ->assertSee('No hay piezas registradas');
    }

    public function test_el_listado_ordena_las_mas_recientes_primero_y_no_muestra_borradas(): void
    {
        Pieza::factory()->create(['nombre' => 'Primera pieza']);
        Pieza::factory()->create(['nombre' => 'Segunda pieza']);
        Pieza::factory()->create(['nombre' => 'Pieza borrada'])->delete();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index'))
            ->assertSeeInOrder(['Segunda pieza', 'Primera pieza'])
            ->assertDontSee('Pieza borrada');
    }

    public function test_el_listado_pagina_de_15_en_15(): void
    {
        Pieza::factory()->count(16)->create();
        $this->actingAs($this->usuarioConRol('operario'));

        $this->assertCount(15, $this->get(route('piezas.index'))->viewData('piezas'));
        $this->assertCount(1, $this->get(route('piezas.index', ['page' => 2]))->viewData('piezas'));
    }

    public function test_el_buscador_filtra_por_nombre(): void
    {
        Pieza::factory()->create(['nombre' => 'Radiador aluminio']);
        Pieza::factory()->create(['nombre' => 'Faro halogeno']);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index', ['q' => 'radiad']))
            ->assertSee('Radiador aluminio')
            ->assertDontSee('Faro halogeno');
    }

    public function test_el_listado_filtra_por_tipo_de_pieza(): void
    {
        $motor = TipoPieza::factory()->create(['nombre' => 'Tipo motor']);
        $luces = TipoPieza::factory()->create(['nombre' => 'Tipo luces']);
        Pieza::factory()->create(['nombre' => 'Culata completa', 'id_tipo_pieza' => $motor->id_pieza]);
        Pieza::factory()->create(['nombre' => 'Piloto trasero', 'id_tipo_pieza' => $luces->id_pieza]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index', ['tipo' => $luces->id_pieza]))
            ->assertSee('Piloto trasero')
            ->assertDontSee('Culata completa');
    }

    public function test_el_listado_filtra_por_estado_y_ofrece_los_estados_reales(): void
    {
        Pieza::factory()->create(['nombre' => 'Bomba de agua', 'estado' => 'Garantizada']);
        Pieza::factory()->create(['nombre' => 'Polea tensora', 'estado' => 'Usada']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('piezas.index'))
            ->assertSee('<option value="Garantizada"', false)
            ->assertSee('<option value="Usada"', false);

        $this->get(route('piezas.index', ['estado' => 'Garantizada']))
            ->assertSee('Bomba de agua')
            ->assertDontSee('Polea tensora');
    }

    public function test_el_listado_filtra_por_disponibilidad_y_stock_bajo(): void
    {
        Pieza::factory()->create(['nombre' => 'Pieza con stock bajo', 'stock' => 1, 'stock_minimo' => 2]);
        Pieza::factory()->create(['nombre' => 'Pieza abundante', 'stock' => 10, 'stock_minimo' => 2]);
        Pieza::factory()->create(['nombre' => 'Pieza agotada', 'stock' => 0, 'stock_minimo' => 1]);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('piezas.index', ['disponibilidad' => 'disponible']))
            ->assertSee('Pieza con stock bajo')
            ->assertSee('Pieza abundante')
            ->assertDontSee('Pieza agotada');

        $this->get(route('piezas.index', ['disponibilidad' => 'agotada']))
            ->assertSee('Pieza agotada')
            ->assertDontSee('Pieza abundante')
            ->assertDontSee('Pieza con stock bajo');

        $this->get(route('piezas.index', ['disponibilidad' => 'stock_bajo']))
            ->assertSee('Pieza con stock bajo')
            ->assertSee('Pieza agotada')
            ->assertDontSee('Pieza abundante');
    }

    public function test_el_listado_marca_la_disponibilidad_y_el_stock_bajo(): void
    {
        Pieza::factory()->create(['stock' => 5, 'stock_minimo' => 5]);
        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index'))
            ->assertSee('>Disponible</span>', false)
            ->assertSee('>Stock bajo</span>', false);

        Pieza::query()->delete();
        Pieza::factory()->create(['stock' => 9, 'stock_minimo' => 2]);
        $this->get(route('piezas.index'))
            ->assertSee('>Disponible</span>', false)
            ->assertDontSee('>Stock bajo</span>', false);

        Pieza::query()->delete();
        Pieza::factory()->create(['stock' => 0, 'stock_minimo' => 2]);
        $this->get(route('piezas.index'))
            ->assertSee('>No disponible</span>', false)
            ->assertSee('>Stock bajo</span>', false);
    }

    public function test_el_listado_sin_coincidencias_ofrece_limpiar_filtros_y_los_filtros_invalidos_se_ignoran(): void
    {
        Pieza::factory()->create(['nombre' => 'Pieza visible']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('piezas.index', ['q' => 'no-existe-nada']))
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertSee('Limpiar filtros');

        $this->get(route('piezas.index', ['tipo' => 'abc', 'disponibilidad' => 'inventada']))
            ->assertOk()
            ->assertSee('Pieza visible');
    }

    public function test_los_botones_del_listado_dependen_del_permiso(): void
    {
        $pieza = Pieza::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.index'))
            ->assertSee(route('piezas.show', $pieza, false))
            ->assertDontSee('Registrar pieza')
            ->assertDontSee(route('piezas.edit', $pieza, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.index'))
            ->assertSee('Registrar pieza')
            ->assertSee(route('piezas.edit', $pieza, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('piezas.index'))
            ->assertSee('Registrar pieza')
            ->assertSee(route('piezas.edit', $pieza, false))
            ->assertSee('value="DELETE"', false);
    }

    // ---------- Ficha ----------

    public function test_la_ficha_muestra_tipo_fabricante_categoria_y_usuario_que_la_registro(): void
    {
        $categoria = Categoria::factory()->create(['nombre' => 'Categoría Eléctrica']);
        $tipo = TipoPieza::factory()->create([
            'nombre' => 'Tipo Arranque',
            'fabricante' => 'Fabricante Test S.L.',
            'id_categoria' => $categoria->categoria_id,
        ]);
        $registrador = User::factory()->create(['name' => 'Marta Registradora', 'email' => 'marta.privada@example.test']);
        $pieza = Pieza::factory()->create([
            'nombre' => 'Motor de arranque Denso',
            'precio' => 89.9,
            'stock' => 6,
            'stock_minimo' => 2,
            'estado' => 'Usada',
            'id_usuario' => $registrador->id,
            'id_tipo_pieza' => $tipo->id_pieza,
        ]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.show', $pieza))
            ->assertOk()
            ->assertSee('Motor de arranque Denso')
            ->assertSee('Tipo Arranque')
            ->assertSee('Fabricante Test S.L.')
            ->assertSee('Categoría Eléctrica')
            ->assertSee('89,90 €')
            ->assertSee('Usada')
            ->assertSee('Marta Registradora')
            ->assertDontSee('marta.privada@example.test');
    }

    public function test_los_botones_de_la_ficha_dependen_del_permiso(): void
    {
        $pieza = Pieza::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('piezas.show', $pieza))
            ->assertDontSee(route('piezas.edit', $pieza, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.show', $pieza))
            ->assertSee(route('piezas.edit', $pieza, false))
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->get(route('piezas.show', $pieza))
            ->assertSee(route('piezas.edit', $pieza, false))
            ->assertSee('value="DELETE"', false);
    }

    public function test_la_ficha_de_una_pieza_borrada_o_inexistente_devuelve_404(): void
    {
        $pieza = Pieza::factory()->create();
        $pieza->delete();
        $this->actingAs($this->usuarioConRol('administrador'));

        $this->get(route('piezas.show', $pieza->id_pieza))->assertNotFound();
        $this->get('/piezas/999999')->assertNotFound();
    }

    // ---------- Alta ----------

    public function test_el_formulario_de_alta_no_pide_el_usuario_y_lista_los_tipos(): void
    {
        TipoPieza::factory()->create(['nombre' => 'Tipo Formulario', 'fabricante' => 'Fab Formulario']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.create'))
            ->assertOk()
            ->assertSee('Tipo Formulario (Fab Formulario)')
            ->assertDontSee('name="id_usuario"', false);
    }

    public function test_un_gerente_registra_una_pieza_a_su_nombre(): void
    {
        $tipo = TipoPieza::factory()->create();
        $gerente = $this->usuarioConRol('gerente');
        $otroUsuario = User::factory()->create();

        $respuesta = $this->actingAs($gerente)
            ->post(route('piezas.store'), $this->datosPieza($tipo, ['id_usuario' => $otroUsuario->id]));

        $pieza = Pieza::where('nombre', 'Alternador Bosch')->firstOrFail();
        $respuesta->assertRedirect(route('piezas.show', $pieza))->assertSessionHas('success');
        $this->assertDatabaseHas('piezas', [
            'id_pieza' => $pieza->id_pieza,
            'estado' => 'Usada',
            'stock' => 4,
            'stock_minimo' => 2,
            'id_tipo_pieza' => $tipo->id_pieza,
            // Se ignora el id_usuario del formulario: manda el usuario autenticado.
            'id_usuario' => $gerente->id,
        ]);
        $this->assertEquals(120.50, (float) $pieza->precio);
    }

    public function test_un_operario_no_puede_registrar_piezas(): void
    {
        $tipo = TipoPieza::factory()->create();
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('piezas.create'))->assertForbidden();
        $this->post(route('piezas.store'), $this->datosPieza($tipo))->assertForbidden();
        $this->assertDatabaseCount('piezas', 0);
    }

    public function test_el_alta_valida_los_campos_obligatorios(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->from(route('piezas.create'))
            ->post(route('piezas.store'), [])
            ->assertRedirect(route('piezas.create'))
            ->assertSessionHasErrors(['nombre', 'precio', 'estado', 'stock', 'stock_minimo', 'id_tipo_pieza']);

        $this->assertDatabaseCount('piezas', 0);
    }

    public function test_el_alta_rechaza_numeros_negativos_decimales_en_stock_y_tipo_inexistente(): void
    {
        $tipo = TipoPieza::factory()->create();
        $this->actingAs($this->usuarioConRol('gerente'));

        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['precio' => '-1']))
            ->assertSessionHasErrors('precio');
        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['precio' => 'caro']))
            ->assertSessionHasErrors('precio');
        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['stock' => -3]))
            ->assertSessionHasErrors('stock');
        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['stock_minimo' => '1.5']))
            ->assertSessionHasErrors('stock_minimo');
        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['id_tipo_pieza' => 999999]))
            ->assertSessionHasErrors('id_tipo_pieza');
        $this->post(route('piezas.store'), $this->datosPieza($tipo, ['estado' => str_repeat('a', 51)]))
            ->assertSessionHasErrors('estado');
        $this->assertDatabaseCount('piezas', 0);
    }

    // ---------- Edición ----------

    public function test_el_formulario_de_edicion_muestra_los_datos_actuales(): void
    {
        $pieza = Pieza::factory()->create(['nombre' => 'Faro editable', 'precio' => 45.5, 'stock' => 8]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('piezas.edit', $pieza))
            ->assertOk()
            ->assertSee('value="Faro editable"', false)
            ->assertSee('value="45.50"', false)
            ->assertSee('value="8"', false);
    }

    public function test_un_gerente_edita_una_pieza_y_se_conserva_quien_la_registro(): void
    {
        $registrador = User::factory()->create();
        $pieza = Pieza::factory()->create(['nombre' => 'Nombre antiguo', 'id_usuario' => $registrador->id]);
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)
            ->put(route('piezas.update', $pieza), $this->datosPieza(
                TipoPieza::find($pieza->id_tipo_pieza),
                ['nombre' => 'Nombre nuevo', 'stock' => 0, 'id_usuario' => $gerente->id],
            ))
            ->assertRedirect(route('piezas.show', $pieza))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('piezas', [
            'id_pieza' => $pieza->id_pieza,
            'nombre' => 'Nombre nuevo',
            'stock' => 0,
            'id_usuario' => $registrador->id,
        ]);
    }

    public function test_la_edicion_valida_los_datos(): void
    {
        $pieza = Pieza::factory()->create(['nombre' => 'Sin cambios']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('piezas.update', $pieza), $this->datosPieza(TipoPieza::find($pieza->id_tipo_pieza), ['nombre' => '', 'stock' => -1]))
            ->assertSessionHasErrors(['nombre', 'stock']);

        $this->assertDatabaseHas('piezas', ['id_pieza' => $pieza->id_pieza, 'nombre' => 'Sin cambios']);
    }

    public function test_un_operario_no_puede_editar_piezas(): void
    {
        $pieza = Pieza::factory()->create(['nombre' => 'Original']);
        $this->actingAs($this->usuarioConRol('operario'));

        $this->get(route('piezas.edit', $pieza))->assertForbidden();
        $this->put(route('piezas.update', $pieza), $this->datosPieza(TipoPieza::find($pieza->id_tipo_pieza), ['nombre' => 'Cambiado']))
            ->assertForbidden();
        $this->assertDatabaseHas('piezas', ['id_pieza' => $pieza->id_pieza, 'nombre' => 'Original']);
    }

    // ---------- Baja ----------

    public function test_un_administrador_elimina_una_pieza_con_borrado_logico(): void
    {
        $pieza = Pieza::factory()->create();

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('piezas.destroy', $pieza))
            ->assertRedirect(route('piezas.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('piezas', ['id_pieza' => $pieza->id_pieza]);
    }

    public function test_ni_el_operario_ni_el_gerente_pueden_eliminar_piezas(): void
    {
        $pieza = Pieza::factory()->create();

        $this->actingAs($this->usuarioConRol('operario'))
            ->delete(route('piezas.destroy', $pieza))
            ->assertForbidden();
        $this->actingAs($this->usuarioConRol('gerente'))
            ->delete(route('piezas.destroy', $pieza))
            ->assertForbidden();

        $this->assertNotSoftDeleted('piezas', ['id_pieza' => $pieza->id_pieza]);
    }
}
