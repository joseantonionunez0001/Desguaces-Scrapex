<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\Vehiculo;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Pantallas 1 a 4: inicio, login, registro y dashboard.
 */
class InicioAutenticacionDashboardWebTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
    }

    public function test_el_inicio_muestra_la_aplicacion_y_los_accesos_a_login_y_registro(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Desguaces Scrapex')
            ->assertSee('Sistema integral de gestión de desguaces')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertDontSee('Ir al dashboard');
    }

    public function test_el_inicio_ofrece_el_dashboard_a_un_usuario_autenticado(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::OPERARIO))
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Ir al dashboard')
            ->assertSee(route('dashboard'), false);
    }

    public function test_el_login_muestra_el_formulario_con_csrf_y_enlace_al_registro(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee(route('register'), false);
    }

    public function test_el_login_muestra_el_error_de_credenciales_incorrectas(): void
    {
        $this->from(route('login'))
            ->post(route('login'), ['email' => 'nadie@example.com', 'password' => 'mala-contrasena'])
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertSee('Las credenciales no son correctas.');
    }

    public function test_el_login_redirige_al_dashboard_tras_autenticarse(): void
    {
        $usuario = $this->usuarioConRol(RoleSeeder::GERENTE);
        $usuario->update(['password' => 'contrasena-segura-1']);

        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'contrasena-segura-1'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_un_usuario_autenticado_no_ve_el_login_ni_el_registro(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::OPERARIO));

        $this->get(route('login'))->assertRedirect();
        $this->get(route('register'))->assertRedirect();
    }

    public function test_el_registro_muestra_el_formulario_sin_campo_de_rol(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('pendiente de autorización')
            ->assertDontSee('name="rol"', false)
            ->assertDontSee('name="role"', false);
    }

    public function test_el_registro_conserva_los_datos_y_muestra_errores_de_validacion(): void
    {
        $datos = [
            'name' => 'Ana Pérez',
            'email' => 'correo-no-valido',
            'password' => 'corta',
            'password_confirmation' => 'distinta',
        ];

        $this->from(route('register'))
            ->post(route('register'), $datos)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), $datos)
            ->assertOk()
            ->assertSee('value="Ana Pérez"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('must be a valid email address');
    }

    public function test_el_dashboard_de_un_usuario_sin_rol_oculta_los_modulos_internos(): void
    {
        $usuario = $this->usuarioConRol();
        Vehiculo::factory()->create();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($usuario->name)
            ->assertSee($usuario->email)
            ->assertSee('Cuenta pendiente de autorización')
            ->assertSee('Logout')
            ->assertDontSee('Accesos rápidos')
            ->assertDontSee('Vehículos')
            ->assertDontSee('Valor del inventario');
    }

    public function test_el_dashboard_calcula_los_indicadores_con_datos_reales(): void
    {
        // El gerente consulta vehículos, piezas y movimientos (el operario ya no ve movimientos).
        $operario = $this->usuarioConRol(RoleSeeder::GERENTE);
        Vehiculo::factory()->count(3)->create();
        $categoria = Categoria::factory()->create();

        Pieza::factory()->create(['precio' => 10, 'stock' => 4, 'stock_minimo' => 2]);
        Pieza::factory()->create(['precio' => 5, 'stock' => 1, 'stock_minimo' => 3]);

        Movimiento::factory()->create(['tipo_movimiento' => 'entrada', 'cantidad' => 7, 'fecha' => now()->toDateString(), 'id_categoria' => $categoria->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'entrada', 'cantidad' => 3, 'fecha' => now()->toDateString(), 'id_categoria' => $categoria->categoria_id]);
        Movimiento::factory()->create(['tipo_movimiento' => 'salida', 'cantidad' => 2, 'fecha' => now()->subYear()->toDateString(), 'id_categoria' => $categoria->categoria_id]);

        $respuesta = $this->actingAs($operario)->get(route('dashboard'))->assertOk();

        $indicadores = $respuesta->viewData('indicadores');

        $this->assertSame(3, $indicadores['vehiculos']['total']);
        $this->assertSame(2, $indicadores['piezas']['total']);
        $this->assertSame(5, $indicadores['piezas']['unidades']);
        $this->assertSame(1, $indicadores['piezas']['stock_bajo']);
        $this->assertEqualsWithDelta(45.0, $indicadores['piezas']['valor'], 0.001);
        $this->assertSame(3, $indicadores['movimientos']['total']);
        $this->assertSame(2, $indicadores['movimientos']['ultimos_30_dias']);

        $porTipo = $indicadores['movimientos']['por_tipo']->keyBy('tipo_movimiento');
        $this->assertEquals(2, $porTipo['entrada']->total);
        $this->assertEquals(10, $porTipo['entrada']->unidades);

        $respuesta->assertSee('Valor del inventario')->assertSee('Últimos movimientos');
    }

    public function test_el_dashboard_sin_datos_muestra_estados_vacios_y_ceros(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::GERENTE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sin movimientos');
    }

    public function test_los_indicadores_respetan_los_permisos_del_usuario(): void
    {
        $usuario = $this->usuarioConRol();
        $usuario->givePermissionTo('vehiculos.ver');

        // Sin ningún rol el dashboard no calcula indicadores ni accesos, aunque tenga un permiso directo.
        $respuesta = $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

        $this->assertSame([], $respuesta->viewData('indicadores'));
        $this->assertSame([], $respuesta->viewData('accesos'));
    }

    public function test_los_accesos_rapidos_solo_incluyen_modulos_autorizados_y_existentes(): void
    {
        // Si las ramas de otros módulos aún no están integradas, se simulan sus rutas
        // para comprobar el filtrado por permisos del menú.
        foreach (['vehiculos.index' => '/vehiculos-simulado', 'piezas.index' => '/piezas-simulado'] as $nombre => $uri) {
            if (! Route::has($nombre)) {
                Route::middleware('web')->get($uri, fn () => 'ok')->name($nombre);
            }
        }
        app('router')->getRoutes()->refreshNameLookups();

        $this->actingAs($this->usuarioConRol(RoleSeeder::OPERARIO));

        $accesos = $this->get(route('dashboard'))->assertOk()->viewData('accesos');

        $rutas = $accesos->pluck('route')->all();

        $this->assertContains('vehiculos.index', $rutas);
        $this->assertContains('piezas.index', $rutas);
        $this->assertNotContains('admin.usuarios.index', $rutas);
        $this->assertNotContains('dashboard', $rutas);
    }

    public function test_el_administrador_ve_el_acceso_a_usuarios_en_el_menu(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.usuarios.index'), false)
            ->assertSee('Usuarios');
    }

    public function test_el_gerente_no_ve_el_acceso_a_usuarios(): void
    {
        $this->actingAs($this->usuarioConRol(RoleSeeder::GERENTE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.usuarios.index'), false);
    }
}
