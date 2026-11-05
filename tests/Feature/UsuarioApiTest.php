<?php

namespace Tests\Feature;

use App\Models\Desguace;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    private function datosValidos(): array
    {
        return [
            'rol_id' => Rol::factory()->create()->id,
            'desguace_id' => Desguace::factory()->create()->id,
            'nombre' => 'Usuario Prueba',
            'correo' => 'prueba@example.com',
            'contrasena' => 'secreto123'
        ];
    }

    public function test_index_devuelve_los_usuarios(): void
    {
        Usuario::factory()->count(2)->create();

        $this->getJson('/api/usuarios')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_usuario_sin_contrasena(): void
    {
        $usuario = Usuario::factory()->create();

        $this->getJson('/api/usuarios/' . $usuario->id)
            ->assertOk()
            ->assertJsonPath('data.correo', $usuario->correo)
            ->assertJsonMissingPath('data.contrasena');
    }

    public function test_store_crea_un_usuario_con_contrasena_cifrada(): void
    {
        $datos = $this->datosValidos();

        $this->postJson('/api/usuarios', $datos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Usuario Prueba')
            ->assertJsonMissingPath('data.contrasena');

        $usuario = Usuario::where('correo', 'prueba@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secreto123', $usuario->contrasena));
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/usuarios', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rol_id', 'desguace_id', 'nombre', 'correo', 'contrasena']);
    }

    public function test_store_rechaza_un_correo_duplicado(): void
    {
        $existente = Usuario::factory()->create();

        $datos = $this->datosValidos();
        $datos['correo'] = $existente->correo;

        $this->postJson('/api/usuarios', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_update_modifica_un_usuario(): void
    {
        $usuario = Usuario::factory()->create();

        $datos = $this->datosValidos();
        $datos['correo'] = 'nuevo@example.com';

        $this->putJson('/api/usuarios/' . $usuario->id, $datos)
            ->assertOk()
            ->assertJsonPath('data.correo', 'nuevo@example.com');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $usuario = Usuario::factory()->create();

        $this->patchJson('/api/usuarios/' . $usuario->id, ['nombre' => 'Nombre Cambiado'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Cambiado');
    }

    public function test_destroy_elimina_un_usuario(): void
    {
        $usuario = Usuario::factory()->create();

        $this->deleteJson('/api/usuarios/' . $usuario->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('usuarios', ['id' => $usuario->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/usuarios/999999')
            ->assertNotFound();
    }
}
