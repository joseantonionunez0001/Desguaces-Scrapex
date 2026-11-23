<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Pieza;
use App\Models\TipoPieza;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantalla de tipos de pieza: listado, alta y edición, cada tipo vinculado a su categoría
 * (única fuente: tipo_piezas.id_categoria).
 */
class TiposPiezaWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Categoria $electricidad;

    private Categoria $motor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->electricidad = Categoria::factory()->create(['nombre' => 'Electricidad', 'id_modulo' => null]);
        $this->motor = Categoria::factory()->create(['nombre' => 'Motor', 'id_modulo' => null]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(?Categoria $categoria = null): array
    {
        return [
            'nombre' => 'Alternador',
            'id_categoria' => ($categoria ?? $this->electricidad)->categoria_id,
            'fabricante' => 'Bosch',
        ];
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('tipos-pieza.index'))->assertRedirect(route('login'));
        $this->post(route('tipos-pieza.store'), [])->assertRedirect(route('login'));
    }

    public function test_usuario_sin_rol_y_operario_reciben_403(): void
    {
        $tipo = TipoPieza::factory()->create(['id_categoria' => $this->electricidad->categoria_id]);

        foreach ([null, RoleSeeder::OPERARIO] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            $this->actingAs($usuario)->get(route('tipos-pieza.index'))->assertForbidden();
            $this->actingAs($usuario)->post(route('tipos-pieza.store'), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->put(route('tipos-pieza.update', $tipo), $this->datosValidos())->assertForbidden();
            $this->actingAs($usuario)->delete(route('tipos-pieza.destroy', $tipo))->assertForbidden();
        }

        $this->assertSame(1, TipoPieza::count());
    }

    public function test_el_parametro_de_ruta_es_tipo_pieza_y_usa_la_clave_id_pieza(): void
    {
        $tipo = TipoPieza::factory()->create(['id_categoria' => $this->electricidad->categoria_id]);

        $this->assertStringEndsWith('/tipos-pieza/'.$tipo->id_pieza, route('tipos-pieza.update', $tipo));
    }

    public function test_el_listado_muestra_cada_tipo_con_su_categoria_y_sus_piezas(): void
    {
        $tipo = TipoPieza::factory()->create(['nombre' => 'Alternador', 'fabricante' => 'Bosch', 'id_categoria' => $this->electricidad->categoria_id]);
        Pieza::factory()->count(2)->create(['id_tipo_pieza' => $tipo->id_pieza]);
        TipoPieza::factory()->create(['nombre' => 'Culata', 'fabricante' => null, 'id_categoria' => $this->motor->categoria_id]);

        foreach (['gerente', RoleSeeder::ADMIN_DESGUACE] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get(route('tipos-pieza.index'))
                ->assertOk()
                ->assertSeeInOrder(['Alternador', 'Electricidad', 'Bosch', '2'])
                ->assertSeeInOrder(['Culata', 'Motor', '—']);
        }
    }

    public function test_el_listado_filtra_por_categoria_y_busca_por_nombre_o_fabricante(): void
    {
        TipoPieza::factory()->create(['nombre' => 'Alternador', 'fabricante' => 'Bosch', 'id_categoria' => $this->electricidad->categoria_id]);
        TipoPieza::factory()->create(['nombre' => 'Motor de arranque', 'fabricante' => 'Valeo', 'id_categoria' => $this->electricidad->categoria_id]);
        TipoPieza::factory()->create(['nombre' => 'Culata', 'fabricante' => 'Valeo', 'id_categoria' => $this->motor->categoria_id]);
        $usuario = $this->usuarioConRol('gerente');

        $total = fn (array $filtros) => $this->actingAs($usuario)->get(route('tipos-pieza.index', $filtros))->viewData('tipos')->total();

        $this->assertSame(3, $total([]));
        $this->assertSame(2, $total(['categoria' => $this->electricidad->categoria_id]));
        $this->assertSame(2, $total(['q' => 'Valeo']));
        $this->assertSame(1, $total(['q' => 'Valeo', 'categoria' => $this->motor->categoria_id]));

        $this->actingAs($usuario)->get(route('tipos-pieza.index', ['q' => 'no-existe']))->assertSee('Sin resultados');
    }

    public function test_los_tipos_de_otra_sede_no_aparecen(): void
    {
        $otraSede = Categoria::factory()->create(['id_desguace' => Desguace::factory()->create()->id_desguace, 'id_modulo' => null]);
        TipoPieza::factory()->create(['nombre' => 'Tipo de otra sede', 'id_categoria' => $otraSede->categoria_id]);

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('tipos-pieza.index'))
            ->assertDontSee('Tipo de otra sede');
    }

    public function test_los_botones_dependen_de_los_permisos(): void
    {
        $tipo = TipoPieza::factory()->create(['id_categoria' => $this->electricidad->categoria_id]);
        $botonDeEdicion = 'data-modal-open="editar-tipo-pieza-'.$tipo->id_pieza.'"';
        $formularioDeBorrado = 'name="_method" value="DELETE"';

        $this->actingAs($this->usuarioConRol('gerente'))->get(route('tipos-pieza.index'))
            ->assertSee('Nuevo tipo de pieza')->assertSee($botonDeEdicion, false)->assertDontSee($formularioDeBorrado, false);

        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))->get(route('tipos-pieza.index'))
            ->assertSee($botonDeEdicion, false)->assertSee($formularioDeBorrado, false);
    }

    public function test_el_formulario_pide_nombre_categoria_y_fabricante_opcional(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))->get(route('tipos-pieza.index'))
            ->assertSee('name="nombre"', false)
            ->assertSee('name="id_categoria"', false)
            ->assertSee('Fabricante (opcional)')
            // La marca y el modelo del vehículo pertenecen a cada pieza.
            ->assertDontSee('name="marca"', false)
            ->assertDontSee('name="modelo"', false)
            // La categoría ya no se escribe como texto: solo se elige la relacionada.
            ->assertDontSee('Categoría (texto)');
    }

    public function test_gerente_y_administrador_crean_tipos_vinculados_a_su_categoria(): void
    {
        foreach (['gerente' => 'Alternador', RoleSeeder::ADMIN_DESGUACE => 'Faro'] as $rol => $nombre) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('tipos-pieza.store'), ['nombre' => $nombre] + $this->datosValidos())
                ->assertRedirect(route('tipos-pieza.index', ['categoria' => $this->electricidad->categoria_id]))
                ->assertSessionHas('success');
        }

        $this->actingAs($this->usuarioConRol('gerente'))
            ->post(route('tipos-pieza.store'), ['nombre' => 'Culata', 'id_categoria' => $this->motor->categoria_id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tipo_piezas', ['nombre' => 'Alternador', 'id_categoria' => $this->electricidad->categoria_id, 'fabricante' => 'Bosch']);
        $this->assertDatabaseHas('tipo_piezas', ['nombre' => 'Culata', 'id_categoria' => $this->motor->categoria_id, 'fabricante' => null]);
    }

    public function test_gerente_edita_un_tipo_y_lo_cambia_de_categoria(): void
    {
        $tipo = TipoPieza::factory()->create(['nombre' => 'Original', 'id_categoria' => $this->electricidad->categoria_id]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('tipos-pieza.update', $tipo), ['nombre' => 'Renombrado', 'id_categoria' => $this->motor->categoria_id])
            ->assertRedirect(route('tipos-pieza.index', ['categoria' => $this->motor->categoria_id]));

        $this->assertSame('Renombrado', $tipo->fresh()->nombre);
        $this->assertSame($this->motor->categoria_id, $tipo->fresh()->id_categoria);
    }

    public function test_la_validacion_rechaza_datos_incompletos_y_categorias_de_otra_sede(): void
    {
        $gerente = $this->usuarioConRol('gerente');
        $ajena = Categoria::factory()->create(['id_desguace' => Desguace::factory()->create()->id_desguace, 'id_modulo' => null]);

        $this->actingAs($gerente)->from(route('tipos-pieza.index'))
            ->post(route('tipos-pieza.store'), ['_modal' => 'crear-tipo'])
            ->assertSessionHasErrors(['nombre', 'id_categoria'])
            ->assertSessionHasInput('_modal', 'crear-tipo');

        $this->actingAs($gerente)->post(route('tipos-pieza.store'), $this->datosValidos($ajena))
            ->assertSessionHasErrors('id_categoria');

        $this->assertSame(0, TipoPieza::withoutGlobalScopes()->count());
    }

    public function test_solo_el_administrador_elimina_tipos_y_no_los_que_tienen_piezas(): void
    {
        $libre = TipoPieza::factory()->create(['id_categoria' => $this->electricidad->categoria_id]);
        $conPiezas = TipoPieza::factory()->create(['id_categoria' => $this->electricidad->categoria_id]);
        Pieza::factory()->create(['id_tipo_pieza' => $conPiezas->id_pieza]);

        $this->actingAs($this->usuarioConRol('gerente'))->delete(route('tipos-pieza.destroy', $libre))->assertForbidden();

        $administrador = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE);
        $this->actingAs($administrador)->delete(route('tipos-pieza.destroy', $libre))->assertSessionHas('success');
        $this->actingAs($administrador)->delete(route('tipos-pieza.destroy', $conPiezas))->assertSessionHas('error');

        $this->assertSoftDeleted($libre);
        $this->assertNotSoftDeleted($conPiezas);
    }
}
