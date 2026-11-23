<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Movimiento;
use App\Models\TipoPieza;
use Database\Factories\DesguaceFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantalla de categorías: alta y listado. Solo se pide el nombre; el desguace lo asigna el servidor.
 * Los tipos se crean en «Tipos de pieza» y las piezas en «Piezas».
 */
class CategoriasWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function categoria(string $nombre = 'Motor'): Categoria
    {
        return Categoria::factory()->create(['nombre' => $nombre, 'id_modulo' => null]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('categorias.index'))->assertRedirect(route('login'));
        $this->post(route('categorias.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_y_operario_reciben_403(): void
    {
        $categoria = $this->categoria();

        foreach ([null, RoleSeeder::OPERARIO] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            $this->actingAs($usuario)->get(route('categorias.index'))->assertForbidden();
            $this->actingAs($usuario)->post(route('categorias.store'), ['nombre' => 'Nueva'])->assertForbidden();
            $this->actingAs($usuario)->put(route('categorias.update', $categoria), ['nombre' => 'Otra'])->assertForbidden();
            $this->actingAs($usuario)->delete(route('categorias.destroy', $categoria))->assertForbidden();
        }

        $this->assertSame('Motor', $categoria->fresh()->nombre);
    }

    public function test_el_listado_muestra_las_categorias_con_sus_contadores(): void
    {
        $categoria = $this->categoria('Electricidad');
        TipoPieza::factory()->count(2)->create(['id_categoria' => $categoria->categoria_id]);
        Movimiento::factory()->count(3)->create(['id_categoria' => $categoria->categoria_id]);

        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('categorias.index'))
                ->assertOk()
                ->assertSee('Electricidad')
                ->assertViewHas('categorias', fn ($categorias) => $categorias->first()->tipos_pieza_count === 2
                    && $categorias->first()->movimientos_count === 3);
        }
    }

    public function test_la_pantalla_ya_no_crea_tipos_ni_piezas(): void
    {
        $categoria = $this->categoria();
        TipoPieza::factory()->create(['id_categoria' => $categoria->categoria_id]);

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))->get(route('categorias.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('tipos-pieza.store').'"', false)
            ->assertDontSee('action="'.route('piezas.store').'"', false)
            ->assertDontSee('name="id_tipo_pieza"', false)
            ->assertDontSee('name="id_modulo"', false)
            ->assertDontSee('name="id_desguace"', false)
            ->assertSee(route('tipos-pieza.index', ['categoria' => $categoria->categoria_id]), false);
    }

    public function test_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Todavía no hay categorías')
            ->assertSee('Nueva categoría');
    }

    public function test_buscador_y_categorias_de_otra_sede(): void
    {
        $this->categoria('Motor');
        $this->categoria('Frenos');
        Categoria::factory()->create(['nombre' => 'Frenos de otra sede', 'id_desguace' => Desguace::factory()->create()->id_desguace, 'id_modulo' => null]);
        $usuario = $this->usuarioConRol('gerente');

        $this->actingAs($usuario)->get(route('categorias.index'))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 2)
            ->assertDontSee('Frenos de otra sede');

        $this->actingAs($usuario)->get(route('categorias.index', ['q' => 'Fren']))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1);

        $this->actingAs($usuario)->get(route('categorias.index', ['q' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_el_listado_se_pagina(): void
    {
        foreach (range(1, 16) as $numero) {
            $this->categoria("Categoría {$numero}");
        }

        $this->actingAs($this->usuarioConRol('gerente'))
            ->get(route('categorias.index', ['page' => 2]))
            ->assertViewHas('categorias', fn ($categorias) => $categorias->count() === 1 && $categorias->total() === 16);
    }

    public function test_los_botones_dependen_del_permiso(): void
    {
        $categoria = $this->categoria();
        $botonDeEdicion = 'data-modal-open="editar-categoria-'.$categoria->categoria_id.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('categorias.index'))
            ->assertSee('Nueva categoría')
            ->assertSee($botonDeEdicion, false)
            ->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))->get(route('categorias.index'))
            ->assertSee($botonDeEdicion, false)
            ->assertSee($formularioDeBorrado, false);
    }

    public function test_gerente_y_administrador_crean_categorias_solo_con_el_nombre(): void
    {
        foreach (['Electricidad' => 'gerente', 'Carrocería' => RoleSeeder::ADMIN_DESGUACE] as $nombre => $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('categorias.store'), ['nombre' => $nombre])
                ->assertRedirect(route('categorias.index'))
                ->assertSessionHas('success');
        }

        // El desguace lo asigna el servidor; el módulo ya no es obligatorio.
        $sede = DesguaceFactory::idPorDefecto();
        $this->assertDatabaseHas('categorias', ['nombre' => 'Electricidad', 'id_desguace' => $sede, 'id_modulo' => null]);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Carrocería', 'id_desguace' => $sede, 'id_modulo' => null]);
    }

    public function test_crear_una_categoria_muestra_el_aviso_flotante_en_la_pagina_siguiente(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->followingRedirects()
            ->post(route('categorias.store'), ['nombre' => 'Motor'])
            ->assertOk()
            ->assertSee('window.flasher?.success', false)
            ->assertSee('correctamente', false);
    }

    public function test_no_se_acepta_otra_sede_ni_un_nombre_repetido_o_vacio(): void
    {
        $gerente = $this->usuarioConRol('gerente');
        $this->categoria('Motor');

        $this->actingAs($gerente)->post(route('categorias.store'), ['nombre' => 'Frenos', 'id_desguace' => Desguace::factory()->create()->id_desguace])
            ->assertSessionHasErrors('id_desguace');
        $this->actingAs($gerente)->post(route('categorias.store'), ['nombre' => 'Motor'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe una categoría con ese nombre en tu desguace.']);
        $this->actingAs($gerente)->post(route('categorias.store'), ['nombre' => '', '_modal' => 'crear-categoria'])
            ->assertSessionHasErrors('nombre')
            ->assertSessionHasInput('_modal', 'crear-categoria');
        $this->actingAs($gerente)->post(route('categorias.store'), ['nombre' => str_repeat('a', 256)])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Categoria::count());
    }

    public function test_el_mismo_nombre_puede_existir_en_otra_sede(): void
    {
        // El gerente se crea primero: su sede es la sede por defecto y la otra es distinta.
        $gerente = $this->usuarioConRol('gerente');
        Categoria::factory()->create(['nombre' => 'Motor', 'id_desguace' => Desguace::factory()->create()->id_desguace, 'id_modulo' => null]);

        $this->actingAs($gerente)
            ->post(route('categorias.store'), ['nombre' => 'Motor'])
            ->assertSessionHasNoErrors();
    }

    public function test_el_modal_se_reabre_con_los_errores_al_volver_al_listado(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->from(route('categorias.index'))
            ->followingRedirects()
            ->post(route('categorias.store'), ['nombre' => '', '_modal' => 'crear-categoria'])
            ->assertOk()
            ->assertSee('id="crear-categoria"', false)
            ->assertSee('data-open-on-load', false);
    }

    public function test_gerente_edita_una_categoria_sin_cambiar_su_sede(): void
    {
        $categoria = $this->categoria('Motor');

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('categorias.update', $categoria), ['nombre' => 'Motor y transmisión'])
            ->assertRedirect(route('categorias.index'));

        $this->assertSame('Motor y transmisión', $categoria->fresh()->nombre);
        $this->assertSame($categoria->id_desguace, $categoria->fresh()->id_desguace);
    }

    public function test_solo_el_administrador_elimina_categorias(): void
    {
        $categoria = $this->categoria();

        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('categorias.destroy', $categoria))->assertForbidden();
        $this->assertNotSoftDeleted($categoria);

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($categoria);
    }

    public function test_no_se_elimina_una_categoria_con_tipos_de_pieza_o_movimientos(): void
    {
        $conTipos = $this->categoria('Con tipos');
        TipoPieza::factory()->create(['id_categoria' => $conTipos->categoria_id]);
        $conMovimientos = $this->categoria('Con movimientos');
        Movimiento::factory()->create(['id_categoria' => $conMovimientos->categoria_id]);
        $administrador = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);

        foreach ([$conTipos, $conMovimientos] as $categoria) {
            $this->actingAs($administrador)
                ->delete(route('categorias.destroy', $categoria))
                ->assertRedirect(route('categorias.index'))
                ->assertSessionHas('error');

            $this->assertNotSoftDeleted($categoria);
        }
    }
}
