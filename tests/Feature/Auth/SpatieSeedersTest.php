<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpatieSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_crean_los_tres_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['administrador', 'gerente', 'operario'] as $rol) {
            $this->assertTrue(Role::where('name', $rol)->where('guard_name', 'web')->exists(), "Falta el rol {$rol}");
        }
    }

    public function test_se_crean_los_permisos_de_cada_recurso(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 16 recursos x 4 acciones + 5 permisos de usuarios + 2 de registro.
        $this->assertSame(71, Permission::count());
        $this->assertTrue(Permission::where('name', 'vehiculos.eliminar')->exists());
        $this->assertTrue(Permission::where('name', 'usuarios.asignar_roles')->exists());
        $this->assertTrue(Permission::where('name', 'registro_sistema.crear')->exists());
    }

    public function test_el_administrador_tiene_todos_los_permisos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Role::findByName(RoleSeeder::ADMINISTRADOR, 'web');

        $this->assertSame(Permission::count(), $admin->permissions()->count());
    }

    public function test_la_matriz_de_gerente_y_operario_es_la_prevista(): void
    {
        $this->seed(DatabaseSeeder::class);

        $gerente = Role::findByName(RoleSeeder::GERENTE, 'web');
        $operario = Role::findByName(RoleSeeder::OPERARIO, 'web');

        // Gerente: crea y edita vehículos, pero no los elimina ni gestiona usuarios.
        $this->assertTrue($gerente->hasPermissionTo('vehiculos.crear'));
        $this->assertTrue($gerente->hasPermissionTo('vehiculos.editar'));
        $this->assertFalse($gerente->hasPermissionTo('vehiculos.eliminar'));
        $this->assertFalse($gerente->hasPermissionTo('usuarios.asignar_roles'));
        $this->assertFalse($gerente->hasPermissionTo('registro_sistema.ver'));

        // Operario: solo consulta, y puede registrar movimientos.
        $this->assertTrue($operario->hasPermissionTo('piezas.ver'));
        $this->assertTrue($operario->hasPermissionTo('movimientos.crear'));
        $this->assertFalse($operario->hasPermissionTo('piezas.crear'));
        $this->assertFalse($operario->hasPermissionTo('vehiculos.eliminar'));
        $this->assertFalse($operario->hasPermissionTo('usuarios.ver'));
    }

    public function test_los_seeders_son_idempotentes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Role::count());
        $this->assertSame(71, Permission::count());
    }

    public function test_el_comando_designa_administrador_a_una_cuenta_existente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $usuario = \App\Models\User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('desguace:asignar-administrador', ['email' => 'admin@example.com'])
            ->assertExitCode(0);

        $this->assertTrue($usuario->fresh()->hasRole('administrador'));
    }

    public function test_el_comando_no_crea_cuentas_nuevas(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('desguace:asignar-administrador', ['email' => 'nadie@example.com'])
            ->assertExitCode(1);

        $this->assertSame(0, \App\Models\User::where('email', 'nadie@example.com')->count());
    }

    public function test_el_permiso_de_seeder_no_depende_de_seeders_de_negocio(): void
    {
        // PermissionSeeder solo crea permisos, sin asignarlos a nadie.
        $this->seed(PermissionSeeder::class);

        $this->assertSame(71, Permission::count());
        $this->assertSame(0, Role::count());
    }
}
