<?php

namespace Tests\Feature;

use App\Models\Patio;
use App\Models\Sector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SectorApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    public function test_index_devuelve_los_sectores(): void
    {
        Sector::factory()->count(2)->create();

        $this->getJson('/api/sectores')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_sector(): void
    {
        $sector = Sector::factory()->create();

        $this->getJson('/api/sectores/' . $sector->id)
            ->assertOk()
            ->assertJsonPath('data.codigo', $sector->codigo);
    }

    public function test_store_crea_un_sector(): void
    {
        $patio = Patio::factory()->create();

        $this->postJson('/api/sectores', [
            'patio_id' => $patio->id,
            'codigo' => 'S-01',
            'tipo_zona' => 'Turismos'
        ])
            ->assertCreated()
            ->assertJsonPath('data.patio_id', $patio->id);

        $this->assertDatabaseHas('sectores', ['codigo' => 'S-01', 'patio_id' => $patio->id]);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/sectores', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['patio_id', 'codigo', 'tipo_zona']);
    }

    public function test_update_modifica_un_sector(): void
    {
        $sector = Sector::factory()->create();

        $this->putJson('/api/sectores/' . $sector->id, [
            'patio_id' => $sector->patio_id,
            'codigo' => 'S-02',
            'tipo_zona' => 'Pesados'
        ])
            ->assertOk()
            ->assertJsonPath('data.tipo_zona', 'Pesados');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $sector = Sector::factory()->create();

        $this->patchJson('/api/sectores/' . $sector->id, ['tipo_zona' => 'Furgonetas'])
            ->assertOk()
            ->assertJsonPath('data.tipo_zona', 'Furgonetas');
    }

    public function test_destroy_elimina_un_sector(): void
    {
        $sector = Sector::factory()->create();

        $this->deleteJson('/api/sectores/' . $sector->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('sectores', ['id' => $sector->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/sectores/999999')
            ->assertNotFound();
    }
}
