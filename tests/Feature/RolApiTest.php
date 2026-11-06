<?php

namespace Tests\Feature;

use App\Models\Rol;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RolApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    private array $datosValidos = [
        'nombre' => 'Rol Prueba'
    ];

    public function test_index_devuelve_los_roles(): void
    {
        Rol::factory()->count(2)->create();

        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_rol(): void
    {
        $rol = Rol::factory()->create();

        $this->getJson('/api/roles/' . $rol->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $rol->nombre);
    }

    public function test_store_crea_un_rol(): void
    {
        $this->postJson('/api/roles', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Rol Prueba');

        $this->assertDatabaseHas('roles', ['nombre' => 'Rol Prueba']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/roles', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_update_modifica_un_rol(): void
    {
        $rol = Rol::factory()->create();

        $this->putJson('/api/roles/' . $rol->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Rol Prueba');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $rol = Rol::factory()->create();

        $this->patchJson('/api/roles/' . $rol->id, ['nombre' => 'Rol Cambiado'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Rol Cambiado');
    }

    public function test_destroy_elimina_un_rol(): void
    {
        $rol = Rol::factory()->create();

        $this->deleteJson('/api/roles/' . $rol->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/roles/999999')
            ->assertNotFound();
    }
}
