<?php

namespace Tests\Feature\Web;

use App\Models\RegistroSistema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class RegistrosSistemaTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
        $this->administrador = $this->usuarioConRol('administrador');
    }

    private function url(array $consulta = []): string
    {
        return route('admin.registros.index', $consulta);
    }

    private function registrar(User $usuario, string $fechaHora): RegistroSistema
    {
        return RegistroSistema::create(['fecha_hora' => $fechaHora, 'id_usuario' => $usuario->id]);
    }

    private function usuario(string $nombre, string $correo): User
    {
        return User::factory()->create(['name' => $nombre, 'email' => $correo]);
    }

    // ---------- Acceso ----------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get($this->url())->assertRedirect(route('login'));
    }

    public function test_un_usuario_sin_rol_recibe_403(): void
    {
        $this->actingAs($this->usuarioConRol(null))->get($this->url())->assertForbidden();
    }

    public function test_gerente_y_operario_reciben_403(): void
    {
        foreach (['gerente', 'operario'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get($this->url())->assertForbidden();
        }
    }

    public function test_el_administrador_ve_los_registros_reales(): void
    {
        $ana = $this->usuario('Ana Registro', 'ana@example.test');
        $this->registrar($ana, '2026-04-02 09:15:30');

        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertOk()
            ->assertSee('Registros del sistema')
            ->assertSee('Ana Registro')
            ->assertSee('ana@example.test')
            ->assertSee('02/04/2026 09:15:30')
            ->assertSee('1 registro');
    }

    public function test_avisa_de_que_el_registro_solo_guarda_usuario_y_fecha(): void
    {
        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertSee('solo guarda el usuario y la fecha y hora');
    }

    public function test_no_hay_columna_de_accion_ni_botones_de_modificacion(): void
    {
        $this->registrar($this->usuario('Luis', 'luis@example.test'), '2026-04-02 09:00:00');

        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertOk()
            ->assertDontSee('>Acción<', false)
            ->assertDontSee('>Acciones<', false)
            ->assertDontSee('Eliminar');
    }

    public function test_sin_registros_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertOk()
            ->assertSee('No hay registros que mostrar')
            ->assertSee('Todavía no se ha guardado ningún registro');
    }

    // ---------- Orden y usuarios eliminados ----------

    public function test_ordena_por_fecha_descendente(): void
    {
        $usuario = $this->usuario('Orden', 'orden@example.test');
        $this->registrar($usuario, '2026-01-01 10:00:00');
        $this->registrar($usuario, '2026-03-01 10:00:00');
        $this->registrar($usuario, '2026-02-01 10:00:00');

        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertSeeInOrder(['01/03/2026 10:00:00', '01/02/2026 10:00:00', '01/01/2026 10:00:00']);
    }

    public function test_un_usuario_eliminado_logicamente_sigue_apareciendo(): void
    {
        $eliminado = $this->usuario('Baja Lógica', 'baja@example.test');
        $this->registrar($eliminado, '2026-04-02 09:00:00');
        $eliminado->delete();

        $this->actingAs($this->administrador)
            ->get($this->url())
            ->assertOk()
            ->assertSee('Baja Lógica')
            ->assertSee('baja@example.test')
            ->assertSee('Usuario eliminado');
    }

    // ---------- Filtros ----------

    public function test_filtra_por_usuario(): void
    {
        $ana = $this->usuario('Ana Filtro', 'ana-filtro@example.test');
        $berta = $this->usuario('Berta Filtro', 'berta-filtro@example.test');
        $this->registrar($ana, '2026-04-02 09:00:00');
        $this->registrar($berta, '2026-04-03 09:00:00');

        $this->actingAs($this->administrador)
            ->get($this->url(['usuario' => $ana->id]))
            ->assertOk()
            ->assertSee('1 registro')
            ->assertSee('02/04/2026 09:00:00')
            ->assertDontSee('03/04/2026 09:00:00');
    }

    public function test_filtra_por_rango_de_fechas_con_extremos_inclusivos(): void
    {
        $usuario = $this->usuario('Rango', 'rango@example.test');
        $this->registrar($usuario, '2026-03-31 23:59:59');
        $this->registrar($usuario, '2026-04-01 00:00:00');
        $this->registrar($usuario, '2026-04-30 23:59:59');
        $this->registrar($usuario, '2026-05-01 00:00:00');

        $respuesta = $this->actingAs($this->administrador)
            ->get($this->url(['desde' => '2026-04-01', 'hasta' => '2026-04-30']))
            ->assertOk()
            ->assertSee('2 registros');

        $respuesta->assertSee('01/04/2026 00:00:00')->assertSee('30/04/2026 23:59:59')
            ->assertDontSee('31/03/2026 23:59:59')->assertDontSee('01/05/2026 00:00:00');
    }

    public function test_busca_por_nombre_y_por_correo_incluidos_los_usuarios_eliminados(): void
    {
        $this->registrar($this->usuario('Carmen Búsqueda', 'carmen@example.test'), '2026-04-02 09:00:00');
        $this->registrar($this->usuario('Diego Otro', 'diego@correo-especial.test'), '2026-04-03 09:00:00');
        $baja = $this->usuario('Elena Baja', 'elena@example.test');
        $this->registrar($baja, '2026-04-04 09:00:00');
        $baja->delete();

        // Las fechas distinguen las filas de la tabla (el desplegable de usuarios también lista nombres y correos).
        $this->actingAs($this->administrador)->get($this->url(['q' => 'Carmen']))
            ->assertSee('02/04/2026 09:00:00')->assertDontSee('03/04/2026 09:00:00')->assertDontSee('04/04/2026 09:00:00');

        $this->actingAs($this->administrador)->get($this->url(['q' => 'correo-especial']))
            ->assertSee('03/04/2026 09:00:00')->assertDontSee('02/04/2026 09:00:00')->assertDontSee('04/04/2026 09:00:00');

        $this->actingAs($this->administrador)->get($this->url(['q' => 'Elena']))
            ->assertSee('04/04/2026 09:00:00')->assertSee('Usuario eliminado')->assertDontSee('02/04/2026 09:00:00');
    }

    public function test_un_filtro_sin_resultados_lo_indica(): void
    {
        $this->registrar($this->usuario('Existe', 'existe@example.test'), '2026-04-02 09:00:00');

        $this->actingAs($this->administrador)
            ->get($this->url(['q' => 'nadie-con-este-nombre']))
            ->assertOk()
            ->assertSee('Ningún registro coincide con los filtros aplicados.');
    }

    public function test_valida_los_filtros(): void
    {
        $this->actingAs($this->administrador)
            ->from($this->url())
            ->get($this->url(['desde' => '2026-05-10', 'hasta' => '2026-05-01']))
            ->assertRedirect($this->url())
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->administrador)
            ->from($this->url())
            ->get($this->url(['desde' => 'no-es-fecha', 'usuario' => 'abc']))
            ->assertSessionHasErrors(['desde', 'usuario']);
    }

    // ---------- Paginación ----------

    public function test_pagina_de_15_en_15_y_conserva_los_filtros(): void
    {
        $usuario = $this->usuario('Paginado', 'paginado@example.test');
        foreach (range(1, 20) as $minuto) {
            $this->registrar($usuario, sprintf('2026-04-02 10:%02d:00', $minuto));
        }

        $this->actingAs($this->administrador)
            ->get($this->url(['q' => 'Paginado']))
            ->assertOk()
            ->assertSee('20 registros')
            ->assertSee('10:20:00')
            ->assertDontSee('10:05:00')
            ->assertSee('q=Paginado', false);

        $this->actingAs($this->administrador)
            ->get($this->url(['q' => 'Paginado', 'page' => 2]))
            ->assertOk()
            ->assertSee('10:05:00')
            ->assertDontSee('10:20:00');
    }

    // ---------- Solo consulta ----------

    public function test_no_existen_rutas_de_modificacion_ni_borrado(): void
    {
        $registro = $this->registrar($this->usuario('Intocable', 'intocable@example.test'), '2026-04-02 09:00:00');

        $this->actingAs($this->administrador)->post($this->url(), ['fecha_hora' => now()])->assertStatus(405);
        $this->actingAs($this->administrador)->put($this->url().'/'.$registro->id_registro, [])->assertNotFound();
        $this->actingAs($this->administrador)->delete($this->url().'/'.$registro->id_registro)->assertNotFound();

        $this->assertDatabaseCount('registro_sistema', 1);
    }
}
