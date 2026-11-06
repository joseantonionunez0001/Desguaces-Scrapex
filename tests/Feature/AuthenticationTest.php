<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_login_es_accesible_para_invitados(): void
    {
        $this->get('/login')
            ->assertOk();
    }

    public function test_un_invitado_es_redirigido_desde_el_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_un_usuario_puede_iniciar_sesion_con_credenciales_validas(): void
    {
        $usuario = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secreto123',
        ]);

        $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'secreto123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_login_rechaza_una_contrasena_incorrecta(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secreto123',
        ]);

        $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'contrasena-mala',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_usuario_autenticado_ve_su_perfil(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk();
    }

    public function test_el_seeder_de_roles_crea_admin_y_usuario(): void
    {
        $this->seed(RoleSeeder::class);

        $usuario = User::factory()->create();
        $usuario->assignRole('admin');

        $this->assertTrue($usuario->hasRole('admin'));
        $this->assertFalse($usuario->hasRole('usuario'));
        $this->assertDatabaseHas('roles', ['name' => 'usuario', 'guard_name' => 'web']);
    }
}
