<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

class AutenticacionWebTest extends TestCase
{
    use RefreshDatabase, PreparaAutorizacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararRoles();
    }

    public function test_la_pagina_principal_muestra_login_y_register(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login')
            ->assertSee('Register');
    }

    public function test_se_puede_registrar_un_usuario_sin_rol(): void
    {
        $this->post('/register', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'contrasena-segura-1',
            'password_confirmation' => 'contrasena-segura-1',
        ])->assertRedirect(route('dashboard'));

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($usuario);
        $this->assertCount(0, $usuario->getRoleNames());
    }

    public function test_la_contrasena_se_guarda_cifrada(): void
    {
        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'contrasena-segura-1',
            'password_confirmation' => 'contrasena-segura-1',
        ]);

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNotSame('contrasena-segura-1', $usuario->password);
        $this->assertTrue(Hash::check('contrasena-segura-1', $usuario->password));
    }

    public function test_no_se_puede_registrar_un_correo_duplicado(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->post('/register', [
            'name' => 'Otra',
            'email' => 'ana@example.com',
            'password' => 'contrasena-segura-1',
            'password_confirmation' => 'contrasena-segura-1',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'ana@example.com')->count());
    }

    public function test_el_registro_no_permite_elegir_un_rol(): void
    {
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'contrasena-segura-1',
            'password_confirmation' => 'contrasena-segura-1',
            'role' => 'administrador',
            'rol' => 'administrador',
        ]);

        $usuario = User::where('email', 'intruso@example.com')->firstOrFail();

        $this->assertFalse($usuario->hasRole('administrador'));
        $this->assertCount(0, $usuario->getRoleNames());
    }

    public function test_el_registro_exige_confirmar_la_contrasena(): void
    {
        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'contrasena-segura-1',
            'password_confirmation' => 'distinta',
        ])->assertSessionHasErrors('password');
    }

    public function test_el_login_con_credenciales_correctas_abre_el_dashboard(): void
    {
        $usuario = User::factory()->create(['password' => 'contrasena-segura-1']);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'contrasena-segura-1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_login_con_contrasena_incorrecta_no_autentica(): void
    {
        $usuario = User::factory()->create(['password' => 'contrasena-segura-1']);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'mala',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_dashboard_requiere_autenticacion(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_el_logout_invalida_la_sesion_y_solo_acepta_post(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        // GET no es válido para cerrar sesión.
        $this->get('/logout')->assertStatus(405);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_el_dashboard_muestra_el_rol_del_usuario(): void
    {
        $usuario = User::factory()->create(['name' => 'Gema Gerente']);
        $usuario->assignRole('gerente');

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Gema Gerente')
            ->assertSee('gerente');
    }

    public function test_el_usuario_sin_rol_ve_el_aviso_de_pendiente(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('pendiente de autorización');
    }
}
