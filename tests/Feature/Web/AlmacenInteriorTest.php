<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Estanteria;
use App\Models\Etiqueta;
use App\Models\Modulo;
use App\Models\Pasillo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AlmacenInteriorTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function urlIndice(array $consulta = []): string
    {
        return route('almacen.interior.index', $consulta);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get($this->urlIndice())->assertRedirect(route('login'));
        $this->post(route('almacen.interior.modulos.store'), [])->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $this->actingAs($this->usuarioConRol(null))->get($this->urlIndice())->assertForbidden();
    }

    public function test_cada_rol_autorizado_ve_la_pantalla_con_los_desguaces(): void
    {
        Desguace::factory()->create(['nombre' => 'Desguace Norte Real']);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get($this->urlIndice())
                ->assertOk()
                ->assertSee('Almacén interior')
                ->assertSee('Desguace Norte Real')
                ->assertSee('Elige un desguace');
        }
    }

    public function test_sin_desguaces_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('gerente'))
            ->get($this->urlIndice())
            ->assertOk()
            ->assertSee('No hay desguaces');
    }

    // ---------- Navegación ----------

    public function test_desguace_elegido_lista_sus_modulos_con_contador_de_pasillos(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Sur']);
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace, 'nombre_modulo' => 'Módulo Alfa']);
        Pasillo::factory()->count(2)->create(['id_modulo' => $modulo->id_modulo]);
        Modulo::factory()->create(['nombre_modulo' => 'Módulo De Otro Desguace']);

        $respuesta = $this->actingAs($this->usuarioConRol('operario'))
            ->get($this->urlIndice(['desguace' => $desguace->id_desguace]))
            ->assertOk()
            ->assertSee('Módulos del desguace')
            ->assertSee('Módulo Alfa')
            ->assertDontSee('Módulo De Otro Desguace');

        $respuesta->assertSeeInOrder(['Módulo Alfa', '2']);
    }

    public function test_se_puede_navegar_por_toda_la_jerarquia_hasta_las_etiquetas(): void
    {
        $desguace = Desguace::factory()->create(['nombre' => 'Desguace Centro']);
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace, 'nombre_modulo' => 'Módulo Zeta']);
        $pasillo = Pasillo::factory()->create(['id_modulo' => $modulo->id_modulo, 'numero_pasillo' => '7']);
        $estanteria = Estanteria::factory()->create(['id_pasillo' => $pasillo->id_pasillo, 'numero_estanteria' => '12']);
        Etiqueta::factory()->create(['id_estanteria' => $estanteria->id_estanteria, 'numero_etiqueta' => 'ET-0042']);
        $usuario = $this->usuarioConRol('operario');

        $this->actingAs($usuario)
            ->get($this->urlIndice(['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo]))
            ->assertOk()
            ->assertSee('Pasillo 7');

        $this->actingAs($usuario)
            ->get($this->urlIndice(['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo, 'pasillo' => $pasillo->id_pasillo]))
            ->assertOk()
            ->assertSee('Estantería 12');

        $this->actingAs($usuario)
            ->get($this->urlIndice([
                'desguace' => $desguace->id_desguace,
                'modulo' => $modulo->id_modulo,
                'pasillo' => $pasillo->id_pasillo,
                'estanteria' => $estanteria->id_estanteria,
            ]))
            ->assertOk()
            ->assertSee('Desguace Centro')
            ->assertSee('Módulo Zeta')
            ->assertSee('Pasillo 7')
            ->assertSee('Estantería 12')
            ->assertSee('ET-0042');
    }

    public function test_un_nivel_vacio_muestra_el_estado_vacio_con_boton_de_crear_segun_permiso(): void
    {
        $desguace = Desguace::factory()->create();
        $url = $this->urlIndice(['desguace' => $desguace->id_desguace]);

        $this->actingAs($this->usuarioConRol('gerente'))->get($url)
            ->assertOk()
            ->assertSee('No hay módulos en este nivel')
            ->assertSee('Nuevo módulo');

        $this->actingAs($this->usuarioConRol('operario'))->get($url)
            ->assertOk()
            ->assertSee('No hay módulos en este nivel')
            ->assertDontSee('Nuevo módulo');
    }

    public function test_las_urls_con_un_camino_incorrecto_devuelven_404(): void
    {
        $desguace = Desguace::factory()->create();
        $moduloAjeno = Modulo::factory()->create();
        $usuario = $this->usuarioConRol('administrador');

        // Desguace inexistente, módulo de otro desguace, módulo sin desguace y valores no numéricos.
        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => 99999]))->assertNotFound();
        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => $desguace->id_desguace, 'modulo' => $moduloAjeno->id_modulo]))->assertNotFound();
        $this->actingAs($usuario)->get($this->urlIndice(['modulo' => $moduloAjeno->id_modulo]))->assertNotFound();
        $this->actingAs($usuario)->get($this->urlIndice(['desguace' => 'abc']))->assertNotFound();
    }

    // ---------- Alta ----------

    public function test_el_gerente_crea_un_modulo_y_vuelve_al_listado_del_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))
            ->post(route('almacen.interior.modulos.store'), [
                'id_desguace' => $desguace->id_desguace,
                'nombre_modulo' => 'Módulo Nuevo',
                'ruta' => ['desguace' => $desguace->id_desguace],
            ])
            ->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('modulos', ['id_desguace' => $desguace->id_desguace, 'nombre_modulo' => 'Módulo Nuevo']);
    }

    public function test_se_crean_pasillos_estanterias_y_etiquetas_en_su_padre(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $usuario = $this->usuarioConRol('gerente');

        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $modulo->id_modulo,
            'numero_pasillo' => 'P-1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo],
        ])->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo]));
        $pasillo = Pasillo::where('numero_pasillo', 'P-1')->firstOrFail();

        $this->actingAs($usuario)->post(route('almacen.interior.estanterias.store'), [
            'id_pasillo' => $pasillo->id_pasillo,
            'numero_estanteria' => 'E-1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo, 'pasillo' => $pasillo->id_pasillo],
        ])->assertSessionHasNoErrors();
        $estanteria = Estanteria::where('numero_estanteria', 'E-1')->firstOrFail();

        $this->actingAs($usuario)->post(route('almacen.interior.etiquetas.store'), [
            'id_estanteria' => $estanteria->id_estanteria,
            'numero_etiqueta' => 'ET-1',
            'ruta' => [
                'desguace' => $desguace->id_desguace,
                'modulo' => $modulo->id_modulo,
                'pasillo' => $pasillo->id_pasillo,
                'estanteria' => $estanteria->id_estanteria,
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('etiquetas', ['id_estanteria' => $estanteria->id_estanteria, 'numero_etiqueta' => 'ET-1']);
    }

    public function test_el_alta_valida_los_campos_obligatorios_y_la_longitud(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $usuario = $this->usuarioConRol('gerente');
        $url = $this->urlIndice(['desguace' => $desguace->id_desguace]);

        $this->actingAs($usuario)->from($url)->post(route('almacen.interior.modulos.store'), [
            'id_desguace' => $desguace->id_desguace,
            'nombre_modulo' => '',
            'ruta' => ['desguace' => $desguace->id_desguace],
        ])->assertRedirect($url)->assertSessionHasErrors('nombre_modulo');

        $this->actingAs($usuario)->from($url)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $modulo->id_modulo,
            'numero_pasillo' => str_repeat('9', 51),
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo],
        ])->assertSessionHasErrors('numero_pasillo');

        $this->assertDatabaseCount('pasillos', 0);
    }

    public function test_un_error_de_validacion_reabre_el_modal_con_el_mensaje_y_conserva_la_ruta(): void
    {
        $desguace = Desguace::factory()->create();
        $url = $this->urlIndice(['desguace' => $desguace->id_desguace]);

        $this->followingRedirects()
            ->actingAs($this->usuarioConRol('gerente'))
            ->from($url)
            ->post(route('almacen.interior.modulos.store'), [
                '_modal' => 'crear-modulos',
                'id_desguace' => $desguace->id_desguace,
                'nombre_modulo' => '',
                'ruta' => ['desguace' => $desguace->id_desguace],
            ])
            ->assertOk()
            ->assertSee('data-open-on-load', false)
            ->assertSee('El campo nombre del módulo es obligatorio.')
            ->assertSee('name="ruta[desguace]" value="'.$desguace->id_desguace.'"', false);
    }

    public function test_el_padre_debe_existir_y_pertenecer_al_camino_mostrado(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $moduloAjeno = Modulo::factory()->create();
        $moduloEliminado = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $moduloEliminado->delete();
        $usuario = $this->usuarioConRol('gerente');

        // Padre inexistente.
        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => 99999,
            'numero_pasillo' => '1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => 99999],
        ])->assertSessionHasErrors('id_modulo');

        // Padre eliminado lógicamente.
        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $moduloEliminado->id_modulo,
            'numero_pasillo' => '1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $moduloEliminado->id_modulo],
        ])->assertSessionHasErrors('id_modulo');

        // Padre que existe pero la ruta mostrada es otra (módulo de otro desguace).
        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $moduloAjeno->id_modulo,
            'numero_pasillo' => '1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $moduloAjeno->id_modulo],
        ])->assertSessionHasErrors('id_modulo');

        // Padre distinto del módulo mostrado en la ruta.
        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $modulo->id_modulo,
            'numero_pasillo' => '1',
            'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $moduloAjeno->id_modulo],
        ])->assertSessionHasErrors('id_modulo');

        // Sin ruta.
        $this->actingAs($usuario)->post(route('almacen.interior.pasillos.store'), [
            'id_modulo' => $modulo->id_modulo,
            'numero_pasillo' => '1',
        ])->assertSessionHasErrors('ruta');

        $this->assertDatabaseCount('pasillos', 0);
    }

    public function test_el_operario_no_puede_crear_editar_ni_eliminar(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace, 'nombre_modulo' => 'Original']);
        $operario = $this->usuarioConRol('operario');

        $this->actingAs($operario)->post(route('almacen.interior.modulos.store'), [
            'id_desguace' => $desguace->id_desguace,
            'nombre_modulo' => 'Intruso',
            'ruta' => ['desguace' => $desguace->id_desguace],
        ])->assertForbidden();

        $this->actingAs($operario)->put(route('almacen.interior.modulos.update', $modulo->id_modulo), [
            'id_desguace' => $desguace->id_desguace,
            'nombre_modulo' => 'Cambiado',
            'ruta' => ['desguace' => $desguace->id_desguace],
        ])->assertForbidden();

        $this->actingAs($operario)->delete(route('almacen.interior.modulos.destroy', $modulo->id_modulo))->assertForbidden();

        $this->assertDatabaseMissing('modulos', ['nombre_modulo' => 'Intruso']);
        $this->assertDatabaseHas('modulos', ['id_modulo' => $modulo->id_modulo, 'nombre_modulo' => 'Original', 'deleted_at' => null]);
    }

    // ---------- Edición ----------

    public function test_el_gerente_edita_un_pasillo(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $pasillo = Pasillo::factory()->create(['id_modulo' => $modulo->id_modulo, 'numero_pasillo' => '1']);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('almacen.interior.pasillos.update', $pasillo->id_pasillo), [
                'id_modulo' => $modulo->id_modulo,
                'numero_pasillo' => '99',
                'ruta' => ['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo],
            ])
            ->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace, 'modulo' => $modulo->id_modulo]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pasillos', ['id_pasillo' => $pasillo->id_pasillo, 'numero_pasillo' => '99']);
    }

    public function test_editar_un_elemento_inexistente_devuelve_404(): void
    {
        $desguace = Desguace::factory()->create();

        $this->actingAs($this->usuarioConRol('gerente'))
            ->put(route('almacen.interior.modulos.update', 99999), [
                'id_desguace' => $desguace->id_desguace,
                'nombre_modulo' => 'X',
                'ruta' => ['desguace' => $desguace->id_desguace],
            ])
            ->assertNotFound();
    }

    // ---------- Baja ----------

    public function test_el_gerente_no_puede_eliminar_pero_el_administrador_si(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);

        $this->actingAs($this->usuarioConRol('gerente'))
            ->delete(route('almacen.interior.modulos.destroy', $modulo->id_modulo))
            ->assertForbidden();
        $this->assertNotSoftDeleted('modulos', ['id_modulo' => $modulo->id_modulo]);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.interior.modulos.destroy', $modulo->id_modulo))
            ->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace]))
            ->assertSessionHas('success');
        $this->assertSoftDeleted('modulos', ['id_modulo' => $modulo->id_modulo]);
    }

    public function test_no_se_elimina_un_elemento_con_hijos_activos(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        Pasillo::factory()->create(['id_modulo' => $modulo->id_modulo]);
        $administrador = $this->usuarioConRol('administrador');

        $this->actingAs($administrador)
            ->delete(route('almacen.interior.modulos.destroy', $modulo->id_modulo))
            ->assertRedirect($this->urlIndice(['desguace' => $desguace->id_desguace]))
            ->assertSessionHas('error');
        $this->assertNotSoftDeleted('modulos', ['id_modulo' => $modulo->id_modulo]);
    }

    public function test_no_se_elimina_un_modulo_con_categorias_activas(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        Categoria::factory()->create(['id_desguace' => $desguace->id_desguace, 'id_modulo' => $modulo->id_modulo, 'id_vehiculo' => null]);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.interior.modulos.destroy', $modulo->id_modulo))
            ->assertSessionHas('error');
        $this->assertNotSoftDeleted('modulos', ['id_modulo' => $modulo->id_modulo]);
    }

    public function test_se_elimina_una_etiqueta_sin_dependientes(): void
    {
        $desguace = Desguace::factory()->create();
        $modulo = Modulo::factory()->create(['id_desguace' => $desguace->id_desguace]);
        $pasillo = Pasillo::factory()->create(['id_modulo' => $modulo->id_modulo]);
        $estanteria = Estanteria::factory()->create(['id_pasillo' => $pasillo->id_pasillo]);
        $etiqueta = Etiqueta::factory()->create(['id_estanteria' => $estanteria->id_estanteria]);

        $this->actingAs($this->usuarioConRol('administrador'))
            ->delete(route('almacen.interior.etiquetas.destroy', $etiqueta->id_etiqueta))
            ->assertRedirect($this->urlIndice([
                'desguace' => $desguace->id_desguace,
                'modulo' => $modulo->id_modulo,
                'pasillo' => $pasillo->id_pasillo,
                'estanteria' => $estanteria->id_estanteria,
            ]));

        $this->assertSoftDeleted('etiquetas', ['id_etiqueta' => $etiqueta->id_etiqueta]);
    }

    public function test_los_botones_dependen_del_permiso(): void
    {
        $desguace = Desguace::factory()->create();
        Modulo::factory()->create(['id_desguace' => $desguace->id_desguace, 'nombre_modulo' => 'Módulo Visible']);
        $url = $this->urlIndice(['desguace' => $desguace->id_desguace]);

        $this->actingAs($this->usuarioConRol('administrador'))->get($url)
            ->assertSee('Nuevo módulo')->assertSee('Editar Módulo Visible')->assertSee('Eliminar Módulo Visible');

        $this->actingAs($this->usuarioConRol('gerente'))->get($url)
            ->assertSee('Nuevo módulo')->assertSee('Editar Módulo Visible')->assertDontSee('Eliminar Módulo Visible');

        $this->actingAs($this->usuarioConRol('operario'))->get($url)
            ->assertSee('Módulo Visible')->assertDontSee('Nuevo módulo')->assertDontSee('Editar Módulo Visible')->assertDontSee('Eliminar Módulo Visible');
    }
}
