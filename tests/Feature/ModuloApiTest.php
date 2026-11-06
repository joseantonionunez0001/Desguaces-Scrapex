<?php

namespace Tests\Feature;

use App\Models\Desguace;
use App\Models\Modulo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuloApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    public function test_index_devuelve_los_modulos(): void
    {
        Modulo::factory()->count(2)->create();

        $this->getJson('/api/modulos')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_modulo(): void
    {
        $modulo = Modulo::factory()->create();

        $this->getJson('/api/modulos/' . $modulo->id)
            ->assertOk()
            ->assertJsonPath('data.nombre_modulo', $modulo->nombre_modulo);
    }

    public function test_store_crea_un_modulo(): void
    {
        $desguace = Desguace::factory()->create();

        $this->postJson('/api/modulos', [
            'nombre_modulo' => 'Modulo A1',
            'desguace_id' => $desguace->id
        ])
            ->assertCreated()
            ->assertJsonPath('data.desguace_id', $desguace->id);

        $this->assertDatabaseHas('modulos', ['nombre_modulo' => 'Modulo A1']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/modulos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre_modulo', 'desguace_id']);
    }

    public function test_store_rechaza_un_desguace_inexistente(): void
    {
        $this->postJson('/api/modulos', [
            'nombre_modulo' => 'Modulo A1',
            'desguace_id' => 999999
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['desguace_id']);
    }

    public function test_update_modifica_un_modulo(): void
    {
        $modulo = Modulo::factory()->create();
        $desguace = Desguace::factory()->create();

        $this->putJson('/api/modulos/' . $modulo->id, [
            'nombre_modulo' => 'Modulo B2',
            'desguace_id' => $desguace->id
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre_modulo', 'Modulo B2');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $modulo = Modulo::factory()->create();

        $this->patchJson('/api/modulos/' . $modulo->id, ['nombre_modulo' => 'Modulo C3'])
            ->assertOk()
            ->assertJsonPath('data.nombre_modulo', 'Modulo C3');
    }

    public function test_destroy_elimina_un_modulo(): void
    {
        $modulo = Modulo::factory()->create();

        $this->deleteJson('/api/modulos/' . $modulo->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('modulos', ['id' => $modulo->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/modulos/999999')
            ->assertNotFound();
    }
}
