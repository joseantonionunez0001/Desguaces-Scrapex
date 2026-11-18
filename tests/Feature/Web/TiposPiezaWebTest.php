<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Pieza;
use App\Models\TipoPieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
            'marca' => 'Seat',
            'modelo' => 'Ibiza',
            'fabricante' => 'Bosch',
            'categoria' => 'Electricidad',
            'id_categoria' => $categoria->categoria_id,
        ];
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->post(route('tipos-pieza.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_recibe_403(): void
    {
        $usuario = $this->usuarioConRol();
        $tipo = TipoPieza::factory()->create();
        $categoria = $this->categoriaCoherente();

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

    public function test_la_pagina_de_tipos_ya_no_existe(): void
    {
        $this->assertFalse(Route::has('tipos-pieza.index'));
    }

    public function test_todos_los_roles_ven_los_tipos_dentro_de_su_categoria_con_sus_piezas(): void
    {
        $categoria = $this->categoriaCoherente('Electricidad', 'Desguace Levante');
        $tipo = TipoPieza::factory()->create([
            'nombre' => 'Alternador 90A',
            'marca' => 'Seat',
            'modelo' => 'Ibiza',
            'fabricante' => 'Bosch',
            'categoria' => 'Texto libre',
            'id_categoria' => $categoria->categoria_id,
        ]);
        Pieza::factory()->count(2)->create(['id_tipo_pieza' => $tipo->id_pieza]);

        foreach (['operario', 'gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('categorias.index'))
                ->assertOk()
                ->assertSee('Electricidad')
                ->assertSee('Alternador 90A')
                ->assertSee('Seat')
                ->assertSee('Tipos de pieza de Seat Ibiza', false)
                ->assertSee('Bosch')
                ->assertSee('2 piezas')
                ->assertViewHas('categorias', fn ($categorias) => $categorias->first()->tiposPieza->first()->piezas_count === 2);
        }
    }

    public function test_una_categoria_sin_tipos_lo_indica(): void
    {
        $this->categoriaCoherente('Frenos');

        $this->actingAs($this->usuarioConRol('operario'))
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Sin tipos de pieza.');
    }

    public function test_el_buscador_encuentra_tipos_por_nombre_fabricante_y_texto_y_muestra_solo_los_que_coinciden(): void
    {
        $frenos = $this->categoriaCoherente('Frenos');
        $motor = $this->categoriaCoherente('Motor');
        TipoPieza::factory()->create(['nombre' => 'Pastillas', 'fabricante' => 'Ferodo', 'categoria' => 'Frenado', 'id_categoria' => $frenos->categoria_id]);
        TipoPieza::factory()->create(['nombre' => 'Disco', 'fabricante' => 'Brembo', 'categoria' => 'Frenado', 'id_categoria' => $frenos->categoria_id]);
        TipoPieza::factory()->create(['nombre' => 'Bujía', 'fabricante' => 'NGK', 'categoria' => 'Encendido', 'id_categoria' => $motor->categoria_id]);
        $operario = $this->usuarioConRol('operario');

        $esperados = [
            'Pastillas' => ['Pastillas'],
            'Ferodo' => ['Pastillas'],
            'Frenado' => ['Disco', 'Pastillas'],
        ];

        foreach ($esperados as $busqueda => $tiposEsperados) {
            $this->actingAs($operario)->get(route('categorias.index', ['q' => $busqueda]))
                ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1
                    && $categorias->first()->categoria_id === $frenos->categoria_id
                    && $categorias->first()->tiposPieza->pluck('nombre')->all() === $tiposEsperados);
        }

        $this->actingAs($operario)->get(route('categorias.index', ['q' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_los_botones_dependen_de_los_permisos(): void
    {
        $categoria = $this->categoriaCoherente();
        $tipo = TipoPieza::factory()->create(['id_categoria' => $categoria->categoria_id]);
        $botonDeEdicion = 'data-modal-open="editar-tipo-pieza-'.$tipo->id_pieza.'"';
        $botonDeAlta = 'data-modal-open="crear-tipo-'.$categoria->categoria_id.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        $this->actingAs($this->usuarioConRol('operario'))->get(route('categorias.index'))
            ->assertDontSee($botonDeAlta, false)->assertDontSee($botonDeEdicion, false)->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('categorias.index'))
            ->assertSee($botonDeAlta, false)->assertSee($botonDeEdicion, false)->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol('administrador'))->get(route('categorias.index'))
            ->assertSee($botonDeAlta, false)->assertSee($botonDeEdicion, false)->assertSee($formularioDeBorrado, false);
    }

    public function test_los_botones_de_anadir_por_marca_y_modelo_dependen_del_permiso_de_alta(): void
    {
        $categoria = $this->categoriaCoherente();
        TipoPieza::factory()->create(['marca' => 'Seat', 'modelo' => 'Ibiza', 'id_categoria' => $categoria->categoria_id]);
        $botonMarca = 'data-modal-open="crear-tipo-'.$categoria->categoria_id.'-'.md5('Seat').'"';
        $botonModelo = 'data-modal-open="crear-tipo-'.$categoria->categoria_id.'-'.md5('Seat|Ibiza').'"';

        $this->actingAs($this->usuarioConRol('operario'))->get(route('categorias.index'))
            ->assertDontSee($botonMarca, false)
            ->assertDontSee($botonModelo, false);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('categorias.index'))
            ->assertSee($botonMarca, false)
            ->assertSee($botonModelo, false)
            ->assertSee('value="Seat"', false)
            ->assertSee('value="Ibiza"', false);
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

    public function test_gerente_y_administrador_crean_desde_la_categoria_y_vuelven_al_listado(): void
    {
        $categoria = $this->categoriaCoherente();

        foreach (['gerente', 'administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('tipos-pieza.store'), $this->datosValidos($categoria))
                ->assertRedirect(route('categorias.index'))
                ->assertSessionHas('success');
        }

        $this->assertDatabaseCount('tipo_piezas', 2);
        $this->assertDatabaseHas('tipo_piezas', [
            'nombre' => 'Alternador 90A',
            'marca' => 'Seat',
            'modelo' => 'Ibiza',
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
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tipo_piezas', [
            'id_pieza' => $tipo->id_pieza,
            'nombre' => 'Alternador 90A',
            'id_categoria' => $categoria->categoria_id,
        ]);
    }

    public function test_validacion_reabre_el_modal_de_la_categoria(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->from(route('categorias.index'))
            ->post(route('tipos-pieza.store'), ['_modal' => 'crear-tipo-1'])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors(['nombre', 'marca', 'modelo', 'fabricante', 'categoria', 'id_categoria'])
            ->assertSessionHasInput('_modal', 'crear-tipo-1');

        $this->assertDatabaseCount('tipo_piezas', 0);
    }

    public function test_solo_el_administrador_elimina_tipos(): void
    {
        $tipo = TipoPieza::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('tipos-pieza.destroy', $tipo))->assertForbidden();
        $this->assertNotSoftDeleted($tipo);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('tipos-pieza.destroy', $tipo))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($tipo);
    }

    public function test_no_se_elimina_un_tipo_con_piezas(): void
    {
        $tipo = TipoPieza::factory()->create();
        Pieza::factory()->create(['id_tipo_pieza' => $tipo->id_pieza]);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('tipos-pieza.destroy', $tipo))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($tipo);
    }
}
