<?php

namespace Tests\Feature;

use App\Models\Desguace;
use App\Models\Patio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PatioApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    public function test_index_devuelve_los_patios(): void
    {
        Patio::factory()->count(2)->create();

        $this->getJson('/api/patios')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_patio(): void
    {
        $patio = Patio::factory()->create();

        $this->getJson('/api/patios/' . $patio->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $patio->nombre);
    }

    public function test_store_crea_un_patio(): void
    {
        $desguace = Desguace::factory()->create();

        $this->postJson('/api/patios', [
            'desguace_id' => $desguace->id,
            'codigo' => 'P-01',
            'nombre' => 'Patio Norte'
        ])
            ->assertCreated()
            ->assertJsonPath('data.desguace_id', $desguace->id);

        $this->assertDatabaseHas('patios', ['codigo' => 'P-01', 'desguace_id' => $desguace->id]);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/patios', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['desguace_id', 'codigo', 'nombre']);
    }

    public function test_update_modifica_un_patio(): void
    {
        $patio = Patio::factory()->create();

        $this->putJson('/api/patios/' . $patio->id, [
            'desguace_id' => $patio->desguace_id,
            'codigo' => 'P-02',
            'nombre' => 'Patio Sur'
        ])
            ->assertOk()
            ->assertJsonPath('data.codigo', 'P-02');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $patio = Patio::factory()->create();

        $this->patchJson('/api/patios/' . $patio->id, ['nombre' => 'Patio Este'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Patio Este');
    }

    public function test_destroy_elimina_un_patio(): void
    {
        $patio = Patio::factory()->create();

        $this->deleteJson('/api/patios/' . $patio->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('patios', ['id' => $patio->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/patios/999999')
            ->assertNotFound();
    }
}
