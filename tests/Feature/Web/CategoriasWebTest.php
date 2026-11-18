<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Movimiento;
use App\Models\TipoPieza;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class CategoriasWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function categoriaCoherente(array $atributos = [], string $desguace = 'Desguace Norte', string $modulo = 'Módulo A'): Categoria
    {
        $desguaceCreado = Desguace::factory()->create(['nombre' => $desguace]);
        $moduloCreado = Modulo::factory()->create(['id_desguace' => $desguaceCreado->id_desguace, 'nombre_modulo' => $modulo]);

        return Categoria::factory()->create($atributos + [
            'id_desguace' => $desguaceCreado->id_desguace,
            'id_modulo' => $moduloCreado->id_modulo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(?Desguace $desguace = null): array
    {
        $desguace ??= Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);

        return [
            'nombre' => 'Transmisión',
            'id_desguace' => $desguace->id_desguace,
            'id_modulo' => $modulo->id_modulo,
            'id_vehiculo' => '',
        ];
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('categorias.index'))->assertRedirect(route('login'));
        $this->post(route('categorias.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_recibe_403(): void
    {
        $usuario = $this->usuarioConRol();
        $categoria = $this->categoriaCoherente();

        $this->actingAs($usuario)->get(route('categorias.index'))->assertForbidden();
        $this->actingAs($usuario)->post(route('categorias.store'), $this->datosValidos())->assertForbidden();
        $this->actingAs($usuario)->put(route('categorias.update', $categoria), $this->datosValidos())->assertForbidden();
        $this->actingAs($usuario)->delete(route('categorias.destroy', $categoria))->assertForbidden();
    }

    public function test_todos_los_roles_ven_las_categorias_con_sus_relaciones_y_contadores(): void
    {
        $vehiculo = Vehiculo::factory()->create(['matricula' => '1234BCD']);
        $categoria = $this->categoriaCoherente(['nombre' => 'Suspensión', 'id_vehiculo' => $vehiculo->id_vehiculo], 'Desguace Levante', 'Módulo Q');
        TipoPieza::factory()->count(2)->create(['id_categoria' => $categoria->categoria_id]);
        Movimiento::factory()->count(3)->create(['id_categoria' => $categoria->categoria_id]);

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('categorias.index'))
                ->assertOk()
                ->assertSee('Suspensión')
                ->assertSee('Desguace Levante')
                ->assertSee('Módulo Q')
                ->assertSee('1234BCD')
                ->assertViewHas('categorias', fn ($categorias) => $categorias->first()->tipos_pieza_count === 2
                    && $categorias->first()->movimientos_count === 3);
        }
    }

    public function test_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Todavía no hay categorías')
            ->assertSee('Nueva categoría');
    }

    public function test_buscador_y_filtro_por_desguace(): void
    {
        $this->categoriaCoherente(['nombre' => 'Motor'], 'Desguace Norte', 'Módulo A');
        $frenos = $this->categoriaCoherente(['nombre' => 'Frenos'], 'Desguace Sur', 'Módulo B');
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)->get(route('categorias.index', ['q' => 'Frenos']))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1);

        $this->actingAs($usuario)->get(route('categorias.index', ['q' => 'Módulo B']))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1
                && $categorias->first()->categoria_id === $frenos->categoria_id);

        $this->actingAs($usuario)->get(route('categorias.index', ['desguace' => $frenos->id_desguace]))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1
                && $categorias->first()->categoria_id === $frenos->categoria_id);

        $this->actingAs($usuario)->get(route('categorias.index', ['q' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_el_listado_se_pagina(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        Categoria::factory()->count(16)->create(['id_desguace' => $desguace->id_desguace, 'id_modulo' => $modulo->id_modulo]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('categorias.index', ['page' => 2]))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->count() === 1 && $categorias->total() === 16);
    }

    public function test_el_operario_no_ve_acciones_de_edicion_y_el_gerente_no_ve_eliminar(): void
    {
        $categoria = $this->categoriaCoherente();
        $botonDeEdicion = 'data-modal-open="editar-categoria-'.$categoria->categoria_id.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        $this->actingAs($this->usuarioConRol('operario'))->get(route('categorias.index'))
            ->assertOk()
            ->assertDontSee('Nueva categoría')
            ->assertDontSee($botonDeEdicion, false)
            ->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Nueva categoría')
            ->assertSee($botonDeEdicion, false)
            ->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('administrador'))->get(route('categorias.index'))
            ->assertSee($botonDeEdicion, false)
            ->assertSee($formularioDeBorrado, false);
    }

    public function test_el_operario_no_puede_crear_editar_ni_eliminar(): void
    {
        $categoria = $this->categoriaCoherente(['nombre' => 'Original']);
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->post(route('categorias.store'), $this->datosValidos())->assertForbidden();
        $this->actingAs($operario)->put(route('categorias.update', $categoria), $this->datosValidos())->assertForbidden();
        $this->actingAs($operario)->delete(route('categorias.destroy', $categoria))->assertForbidden();

        $this->assertDatabaseCount('categorias', 1);
        $this->assertSame('Original', $categoria->fresh()->nombre);
    }

    public function test_gerente_y_administrador_crean_categorias(): void
    {
        foreach (['gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('categorias.store'), $this->datosValidos())
                ->assertRedirect(route('categorias.index'))
                ->assertSessionHas('flasher::envelopes');
        }

        $this->assertDatabaseCount('categorias', 2);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Transmisión', 'id_vehiculo' => null]);
    }

    public function test_crear_una_categoria_deja_el_aviso_flotante_para_la_pagina_siguiente(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->post(route('categorias.store'), $this->datosValidos())
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('flasher::envelopes', fn ($sobres) => str_contains(implode('', $sobres), 'Categoría creada correctamente.'));
    }

    public function test_se_puede_asociar_un_vehiculo_y_quitarlo_al_editar(): void
    {
        $categoria = $this->categoriaCoherente();
        $vehiculo = Vehiculo::factory()->create();
        $datos = [
            'nombre' => 'Motor',
            'id_desguace' => $categoria->id_desguace,
            'id_modulo' => $categoria->id_modulo,
            'id_vehiculo' => $vehiculo->id_vehiculo,
        ];
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)->put(route('categorias.update', $categoria), $datos)
            ->assertRedirect(route('categorias.index'));
        $this->assertSame($vehiculo->id_vehiculo, $categoria->fresh()->id_vehiculo);

        $this->actingAs($gerente)->put(route('categorias.update', $categoria), ['id_vehiculo' => ''] + $datos)
            ->assertRedirect(route('categorias.index'));
        $this->assertNull($categoria->fresh()->id_vehiculo);
    }

    public function test_gerente_edita_una_categoria(): void
    {
        $categoria = $this->categoriaCoherente(['nombre' => 'Original']);
        $datos = ['nombre' => 'Renombrada', 'id_desguace' => $categoria->id_desguace, 'id_modulo' => $categoria->id_modulo, 'id_vehiculo' => ''];

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('categorias.update', $categoria), $datos)
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertDatabaseHas('categorias', ['categoria_id' => $categoria->categoria_id, 'nombre' => 'Renombrada']);
    }

    public function test_validacion_de_campos_obligatorios_y_reapertura_del_modal(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->from(route('categorias.index'))
            ->post(route('categorias.store'), ['_modal' => 'crear-categoria'])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors(['nombre', 'id_desguace', 'id_modulo'])
            ->assertSessionHasInput('_modal', 'crear-categoria');

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_el_modal_se_reabre_con_los_errores_al_volver_al_listado(): void
    {
        $this->withSession(['_old_input' => ['_modal' => 'crear-categoria']])
            ->actingAs($this->usuarioConRol('gerente'))
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('data-open-on-load', false);
    }

    public function test_validacion_de_valores_inexistentes_y_longitud(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->post(route('categorias.store'), [
                'nombre' => str_repeat('a', 256),
                'id_desguace' => 999999,
                'id_modulo' => 999999,
                'id_vehiculo' => 999999,
            ])
            ->assertSessionHasErrors(['nombre', 'id_desguace', 'id_modulo', 'id_vehiculo']);
    }

    public function test_el_modulo_debe_pertenecer_al_desguace_elegido(): void
    {
        $desguace = Desguace::factory()->create();
        $moduloDeOtroDesguace = Modulo::factory()->create();
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)
            ->post(route('categorias.store'), [
                'nombre' => 'Incoherente',
                'id_desguace' => $desguace->id_desguace,
                'id_modulo' => $moduloDeOtroDesguace->id_modulo,
            ])
            ->assertSessionHasErrors(['id_modulo' => 'El módulo seleccionado no pertenece al desguace elegido.']);

        $categoria = $this->categoriaCoherente();
        $this->actingAs($gerente)
            ->put(route('categorias.update', $categoria), [
                'nombre' => 'Incoherente',
                'id_desguace' => $categoria->id_desguace,
                'id_modulo' => $moduloDeOtroDesguace->id_modulo,
            ])
            ->assertSessionHasErrors('id_modulo');

        $this->assertDatabaseMissing('categorias', ['nombre' => 'Incoherente']);
    }

    public function test_solo_el_administrador_elimina_categorias(): void
    {
        $categoria = $this->categoriaCoherente();

        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('categorias.destroy', $categoria))->assertForbidden();
        $this->assertNotSoftDeleted($categoria);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('flasher::envelopes');

        $this->assertSoftDeleted($categoria);
    }

    public function test_no_se_elimina_una_categoria_con_tipos_de_pieza_o_movimientos(): void
    {
        $conTipos = $this->categoriaCoherente();
        TipoPieza::factory()->create(['id_categoria' => $conTipos->categoria_id]);
        $conMovimientos = $this->categoriaCoherente();
        Movimiento::factory()->create(['id_categoria' => $conMovimientos->categoria_id]);
        $administrador = $this->usuarioConRol('administrador');

        foreach ([$conTipos, $conMovimientos] as $categoria) {
            $this->actingAs($administrador)
                ->delete(route('categorias.destroy', $categoria))
                ->assertRedirect(route('categorias.index'))
                ->assertSessionHas('flasher::envelopes');

            $this->assertNotSoftDeleted($categoria);
        }
    }
}
