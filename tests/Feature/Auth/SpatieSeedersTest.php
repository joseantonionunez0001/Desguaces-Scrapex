<?php

namespace Tests\Feature\Auth;

use App\Models\Desguace;
use App\Models\User;
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

    public function test_se_crean_los_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (RoleSeeder::ROLES as $rol) {
            $this->assertTrue(Role::where('name', $rol)->where('guard_name', 'web')->exists(), "Falta el rol {$rol}");
        }
    }

    public function test_se_crean_los_permisos_de_cada_recurso(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 16 recursos x 4 acciones + 7 permisos de usuarios/registro + 2 de plataforma.
        $this->assertSame(73, Permission::count());
        $this->assertTrue(Permission::where('name', 'vehiculos.eliminar')->exists());
        $this->assertTrue(Permission::where('name', 'usuarios.asignar_roles')->exists());
        $this->assertTrue(Permission::where('name', 'usuarios.asignar_desguace')->exists());
        $this->assertTrue(Permission::where('name', 'desguaces.asignar_administrador')->exists());
        $this->assertTrue(Permission::where('name', 'registro_sistema.crear')->exists());
    }

    public function test_el_rol_anterior_administrador_queda_sin_permisos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $anterior = Role::findByName(RoleSeeder::ADMINISTRADOR, 'web');
        $this->assertSame(0, $anterior->permissions()->count());
    }

    public function test_la_plataforma_no_tiene_permisos_de_inventario(): void
    {
        $this->seed(DatabaseSeeder::class);
        $plataforma = Role::findByName(RoleSeeder::PLATAFORMA, 'web');

        $this->assertTrue($plataforma->hasPermissionTo('desguaces.crear'));
        $this->assertTrue($plataforma->hasPermissionTo('desguaces.asignar_administrador'));
        $this->assertTrue($plataforma->hasPermissionTo('usuarios.asignar_desguace'));
        foreach (['vehiculos', 'piezas', 'movimientos', 'modulos', 'ubicaciones_vehiculo'] as $recurso) {
            $this->assertFalse($plataforma->hasPermissionTo("{$recurso}.ver"), "La plataforma no debe ver {$recurso}");
        }
        $this->assertFalse($plataforma->hasPermissionTo('usuarios.crear'));
    }

    public function test_el_administrador_de_desguace_gestiona_equipo_e_inventario_pero_no_plataforma(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Role::findByName(RoleSeeder::ADMIN_DESGUACE, 'web');

        $this->assertTrue($admin->hasPermissionTo('vehiculos.eliminar'));
        $this->assertTrue($admin->hasPermissionTo('usuarios.crear'));
        $this->assertTrue($admin->hasPermissionTo('usuarios.asignar_roles'));
        $this->assertTrue($admin->hasPermissionTo('desguaces.ver'));
        $this->assertFalse($admin->hasPermissionTo('desguaces.crear'));
        $this->assertFalse($admin->hasPermissionTo('desguaces.editar'));
        $this->assertFalse($admin->hasPermissionTo('desguaces.asignar_administrador'));
        $this->assertFalse($admin->hasPermissionTo('usuarios.asignar_desguace'));
    }

    public function test_la_matriz_de_gerente_y_operario_es_la_prevista(): void
    {
        $this->seed(DatabaseSeeder::class);
        $gerente = Role::findByName(RoleSeeder::GERENTE, 'web');
        $operario = Role::findByName(RoleSeeder::OPERARIO, 'web');

        // Gerente: crea y edita vehiculos y piezas, pero no los elimina ni gestiona usuarios.
        $this->assertTrue($gerente->hasPermissionTo('vehiculos.crear'));
        $this->assertTrue($gerente->hasPermissionTo('piezas.editar'));
        $this->assertFalse($gerente->hasPermissionTo('vehiculos.eliminar'));
        $this->assertFalse($gerente->hasPermissionTo('usuarios.ver'));
        $this->assertFalse($gerente->hasPermissionTo('usuarios.asignar_roles'));
        $this->assertFalse($gerente->hasPermissionTo('registro_sistema.ver'));

        // Operario: solo lectura de vehiculos y piezas.
        $this->assertTrue($operario->hasPermissionTo('piezas.ver'));
        $this->assertTrue($operario->hasPermissionTo('vehiculos.ver'));
        foreach (Permission::where('name', 'like', '%.%')->pluck('name') as $permiso) {
            if (preg_match('/\.(crear|editar|eliminar)$/', $permiso)) {
                $this->assertFalse($operario->hasPermissionTo($permiso), "El operario no debe tener {$permiso}");
            }
        }
        $this->assertFalse($operario->hasPermissionTo('modulos.ver'));
        $this->assertFalse($operario->hasPermissionTo('usuarios.ver'));
    }

    public function test_los_seeders_son_idempotentes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(RoleSeeder::ROLES), Role::count());
        $this->assertSame(73, Permission::count());
    }

    public function test_sincronizar_permisos_no_modifica_usuarios(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sede = Desguace::factory()->create();
        $usuario = User::factory()->create(['id_desguace' => $sede->id_desguace]);
        $usuario->assignRole(RoleSeeder::GERENTE);

        $this->artisan('scrapex:sincronizar-permisos')->assertExitCode(0);

        $usuario->refresh();
        $this->assertSame($sede->id_desguace, $usuario->id_desguace);
        $this->assertSame([RoleSeeder::GERENTE], $usuario->getRoleNames()->all());
    }

    public function test_el_comando_habilita_al_primer_administrador_de_plataforma(): void
    {
        $this->seed(DatabaseSeeder::class);
        $usuario = User::factory()->create(['email' => 'plataforma@example.com', 'id_desguace' => null]);

        $this->artisan('scrapex:habilitar-plataforma', ['email' => 'plataforma@example.com'])
            ->assertExitCode(0);

        $this->assertTrue($usuario->fresh()->esAdministradorPlataforma());
    }

    public function test_el_comando_no_habilita_a_una_cuenta_de_un_desguace(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sede = Desguace::factory()->create();
        $usuario = User::factory()->create(['email' => 'local@example.com', 'id_desguace' => $sede->id_desguace]);

        $this->artisan('scrapex:habilitar-plataforma', ['email' => 'local@example.com'])
            ->assertExitCode(1);

        $this->assertFalse($usuario->fresh()->esAdministradorPlataforma());
    }

    public function test_el_comando_no_crea_cuentas_nuevas(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('scrapex:habilitar-plataforma', ['email' => 'nadie@example.com'])
            ->assertExitCode(1);

        $this->assertSame(0, User::where('email', 'nadie@example.com')->count());
    }

    public function test_el_comando_asigna_sede_y_rol_a_una_cuenta_existente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sede = Desguace::factory()->create();
        $usuario = User::factory()->create(['email' => 'operario@example.com', 'id_desguace' => null]);

        $this->artisan('scrapex:asignar-usuario', [
            'email' => 'operario@example.com',
            'id_desguace' => $sede->id_desguace,
            'rol' => RoleSeeder::OPERARIO,
        ])->assertExitCode(0);

        $usuario->refresh();
        $this->assertSame($sede->id_desguace, $usuario->id_desguace);
        $this->assertTrue($usuario->hasRole(RoleSeeder::OPERARIO));
    }

    public function test_el_comando_rechaza_roles_que_no_son_de_desguace(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sede = Desguace::factory()->create();
        User::factory()->create(['email' => 'x@example.com']);

        $this->artisan('scrapex:asignar-usuario', [
            'email' => 'x@example.com',
            'id_desguace' => $sede->id_desguace,
            'rol' => RoleSeeder::PLATAFORMA,
        ])->assertExitCode(1);
    }

    public function test_el_permiso_de_seeder_no_depende_de_seeders_de_negocio(): void
    {
        // PermissionSeeder solo crea permisos, sin asignarlos a nadie.
        $this->seed(PermissionSeeder::class);

        $this->assertSame(73, Permission::count());
        $this->assertSame(0, Role::count());
    }
}
