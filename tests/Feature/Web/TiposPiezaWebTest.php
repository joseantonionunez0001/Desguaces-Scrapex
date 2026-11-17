<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Pieza;
use App\Models\TipoPieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class TiposPiezaWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function categoriaCoherente(string $nombre = 'Motor', string $desguace = 'Desguace Norte'): Categoria
    {
        $desguaceCreado = Desguace::factory()->create(['nombre' => $desguace]);
        $modulo = Modulo::factory()->create(['id_desguace' => $desguaceCreado->id_desguace]);

        return Categoria::factory()->create([
            'nombre' => $nombre,
            'id_desguace' => $desguaceCreado->id_desguace,
            'id_modulo' => $modulo->id_modulo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(Categoria $categoria): array
    {
        return [
            'nombre' => 'Alternador 90A',
            'fabricante' => 'Bosch',
            'categoria' => 'Electricidad',
            'id_categoria' => $categoria->categoria_id,
        ];
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('tipos-pieza.index'))->assertRedirect(route('login'));
        $this->post(route('tipos-pieza.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_recibe_403(): void
    {
        $usuario = $this->usuarioConRol();
        $tipo = TipoPieza::factory()->create();
        $categoria = $this->categoriaCoherente();

        $this->actingAs($usuario)->get(route('tipos-pieza.index'))->assertForbidden();
        $this->actingAs($usuario)->post(route('tipos-pieza.store'), $this->datosValidos($categoria))->assertForbidden();
        $this->actingAs($usuario)->put(route('tipos-pieza.update', $tipo), $this->datosValidos($categoria))->assertForbidden();
        $this->actingAs($usuario)->delete(route('tipos-pieza.destroy', $tipo))->assertForbidden();
    }

    public function test_el_parametro_de_ruta_es_tipo_pieza_y_usa_la_clave_id_pieza(): void
    {
        $tipo = TipoPieza::factory()->create();

        $this->assertStringEndsWith('/tipos-pieza/'.$tipo->id_pieza, route('tipos-pieza.update', $tipo));
        $this->assertSame(['tipoPieza'], app('router')->getRoutes()->getByName('tipos-pieza.update')->parameterNames());
    }

    public function test_todos_los_roles_ven_los_tipos_con_categoria_y_numero_de_piezas(): void
    {
        $categoria = $this->categoriaCoherente('Electricidad', 'Desguace Levante');
        $tipo = TipoPieza::factory()->create([
            'nombre' => 'Alternador 90A',
            'fabricante' => 'Bosch',
            'categoria' => 'Texto libre',
            'id_categoria' => $categoria->categoria_id,
        ]);
        Pieza::factory()->count(2)->create(['id_tipo_pieza' => $tipo->id_pieza]);

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('tipos-pieza.index'))
                ->assertOk()
                ->assertSee('Alternador 90A')
                ->assertSee('Bosch')
                ->assertSee('Texto libre')
                ->assertSee('Electricidad')
                ->assertSee('Desguace Levante')
                ->assertViewHas('tiposPieza', fn ($tipos) => $tipos->first()->piezas_count === 2);
        }
    }

    public function test_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('tipos-pieza.index'))
            ->assertOk()
            ->assertSee('Todavía no hay tipos de pieza');
    }

    public function test_buscador_y_filtro_por_categoria(): void
    {
        $motor = $this->categoriaCoherente('Motor');
        $frenos = $this->categoriaCoherente('Frenos');
        TipoPieza::factory()->create(['nombre' => 'Pastillas', 'fabricante' => 'Ferodo', 'categoria' => 'Frenado', 'id_categoria' => $frenos->categoria_id]);
        TipoPieza::factory()->create(['nombre' => 'Bujía', 'fabricante' => 'NGK', 'categoria' => 'Encendido', 'id_categoria' => $motor->categoria_id]);
        $usuario = $this->usuarioConRol('operario');

        foreach (['Pastillas', 'Ferodo', 'Frenado'] as $busqueda) {
            $this->actingAs($usuario)->get(route('tipos-pieza.index', ['q' => $busqueda]))
                ->assertViewHas('tiposPieza', fn ($tipos) => $tipos->total() === 1 && $tipos->first()->nombre === 'Pastillas');
        }

        $this->actingAs($usuario)->get(route('tipos-pieza.index', ['categoria' => $motor->categoria_id]))
            ->assertViewHas('tiposPieza', fn ($tipos) => $tipos->total() === 1 && $tipos->first()->nombre === 'Bujía');

        $this->actingAs($usuario)->get(route('tipos-pieza.index', ['q' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_el_listado_se_pagina(): void
    {
        $categoria = $this->categoriaCoherente();
        TipoPieza::factory()->count(16)->create(['id_categoria' => $categoria->categoria_id]);

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('tipos-pieza.index', ['page' => 2]))
            ->assertViewHas('tiposPieza', fn ($tipos) => $tipos->count() === 1 && $tipos->total() === 16);
    }

    public function test_los_botones_dependen_de_los_permisos(): void
    {
        $tipo = TipoPieza::factory()->create();
        $botonDeEdicion = 'data-modal-open="editar-tipo-pieza-'.$tipo->id_pieza.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        $this->actingAs($this->usuarioConRol('operario'))->get(route('tipos-pieza.index'))
            ->assertDontSee('Nuevo tipo de pieza')->assertDontSee($botonDeEdicion, false)->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('tipos-pieza.index'))
            ->assertSee('Nuevo tipo de pieza')->assertSee($botonDeEdicion, false)->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('administrador'))->get(route('tipos-pieza.index'))
            ->assertSee($botonDeEdicion, false)->assertSee($formularioDeBorrado, false);
    }

    public function test_el_operario_no_puede_crear_editar_ni_eliminar(): void
    {
        $categoria = $this->categoriaCoherente();
        $tipo = TipoPieza::factory()->create(['nombre' => 'Original']);
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->post(route('tipos-pieza.store'), $this->datosValidos($categoria))->assertForbidden();
        $this->actingAs($operario)->put(route('tipos-pieza.update', $tipo), $this->datosValidos($categoria))->assertForbidden();
        $this->actingAs($operario)->delete(route('tipos-pieza.destroy', $tipo))->assertForbidden();

        $this->assertDatabaseCount('tipo_piezas', 1);
        $this->assertSame('Original', $tipo->fresh()->nombre);
    }

    public function test_gerente_y_administrador_crean_conservando_ambos_campos_de_categoria(): void
    {
        $categoria = $this->categoriaCoherente();

        foreach (['gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('tipos-pieza.store'), $this->datosValidos($categoria))
                ->assertRedirect(route('tipos-pieza.index'))
                ->assertSessionHas('success');
        }

        $this->assertDatabaseCount('tipo_piezas', 2);
        $this->assertDatabaseHas('tipo_piezas', [
            'nombre' => 'Alternador 90A',
            'fabricante' => 'Bosch',
            'categoria' => 'Electricidad',
            'id_categoria' => $categoria->categoria_id,
        ]);
    }

    public function test_gerente_edita_un_tipo(): void
    {
        $categoria = $this->categoriaCoherente();
        $tipo = TipoPieza::factory()->create(['nombre' => 'Original']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('tipos-pieza.update', $tipo), $this->datosValidos($categoria))
            ->assertRedirect(route('tipos-pieza.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tipo_piezas', [
            'id_pieza' => $tipo->id_pieza,
            'nombre' => 'Alternador 90A',
            'id_categoria' => $categoria->categoria_id,
        ]);
    }

    public function test_validacion_de_campos_obligatorios_y_valores_incorrectos(): void
    {
        $gerente = $this->usuarioConRol('gerente');

        $this->actingAs($gerente)
            ->from(route('tipos-pieza.index'))
            ->post(route('tipos-pieza.store'), ['_modal' => 'crear-tipo-pieza'])
            ->assertRedirect(route('tipos-pieza.index'))
            ->assertSessionHasErrors(['nombre', 'fabricante', 'categoria', 'id_categoria'])
            ->assertSessionHasInput('_modal', 'crear-tipo-pieza');

        $this->actingAs($gerente)
            ->post(route('tipos-pieza.store'), [
                'nombre' => str_repeat('a', 256),
                'fabricante' => str_repeat('a', 256),
                'categoria' => str_repeat('a', 256),
                'id_categoria' => 999999,
            ])
            ->assertSessionHasErrors(['nombre', 'fabricante', 'categoria', 'id_categoria']);

        $this->assertDatabaseCount('tipo_piezas', 0);
    }

    public function test_solo_el_administrador_elimina_tipos(): void
    {
        $tipo = TipoPieza::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('tipos-pieza.destroy', $tipo))->assertForbidden();
        $this->assertNotSoftDeleted($tipo);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('tipos-pieza.destroy', $tipo))
            ->assertRedirect(route('tipos-pieza.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($tipo);
    }

    public function test_no_se_elimina_un_tipo_con_piezas(): void
    {
        $tipo = TipoPieza::factory()->create();
        Pieza::factory()->create(['id_tipo_pieza' => $tipo->id_pieza]);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('tipos-pieza.destroy', $tipo))
            ->assertRedirect(route('tipos-pieza.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($tipo);
    }
}
