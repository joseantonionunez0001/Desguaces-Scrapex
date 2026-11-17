<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UsuarioAdministradorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioAdministradorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_el_administrador_con_la_contrasena_del_entorno(): void
    {
        config(['administrador.password' => 'contrasena-de-prueba']);
        $this->seed(RoleSeeder::class);

        $this->seed(UsuarioAdministradorSeeder::class);

        $usuario = User::where('email', 'javier.ruiz@davante.es')->firstOrFail();
        $this->assertTrue($usuario->hasRole(RoleSeeder::ADMINISTRADOR));
        $this->assertTrue(Hash::check('contrasena-de-prueba', $usuario->password));
    }

    public function test_no_crea_nada_si_falta_la_contrasena_del_entorno(): void
    {
        config(['administrador.password' => null]);
        $this->seed(RoleSeeder::class);

        $this->seed(UsuarioAdministradorSeeder::class);

        $this->assertFalse(User::where('email', 'javier.ruiz@davante.es')->exists());
    }

    public function test_ejecutarlo_dos_veces_no_duplica_el_usuario(): void
    {
        config(['administrador.password' => 'contrasena-de-prueba']);
        $this->seed(RoleSeeder::class);

        $this->seed(UsuarioAdministradorSeeder::class);
        $this->seed(UsuarioAdministradorSeeder::class);

        $this->assertSame(1, User::where('email', 'javier.ruiz@davante.es')->count());
    }
}
