<?php

namespace Tests\Feature;

use App\Models\Estanteria;
use App\Models\Etiqueta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EstanteriaApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    public function test_index_devuelve_las_estanterias(): void
    {
        Estanteria::factory()->count(2)->create();

        $this->getJson('/api/estanterias')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_store_crea_una_estanteria(): void
    {
        $this->postJson('/api/estanterias', ['numero_estanteria' => 7])
            ->assertCreated()
            ->assertJsonPath('data.numero_estanteria', 7);

        $this->assertDatabaseHas('estanterias', ['numero_estanteria' => 7]);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/estanterias', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero_estanteria']);
    }

    public function test_patch_modifica_solo_el_campo_enviado(): void
    {
        $estanteria = Estanteria::factory()->create();

        $this->patchJson('/api/estanterias/' . $estanteria->id, ['numero_estanteria' => 42])
            ->assertOk()
            ->assertJsonPath('data.numero_estanteria', 42);
    }

    public function test_destroy_no_permite_borrar_una_estanteria_con_etiquetas(): void
    {
        $estanteria = Estanteria::factory()->create();
        Etiqueta::factory()->create(['estanteria_id' => $estanteria->id]);

        $this->deleteJson('/api/estanterias/' . $estanteria->id)
            ->assertStatus(409)
            ->assertJsonPath('success', 0);

        $this->assertDatabaseHas('estanterias', ['id' => $estanteria->id]);
    }

    public function test_destroy_elimina_una_estanteria_sin_etiquetas(): void
    {
        $estanteria = Estanteria::factory()->create();

        $this->deleteJson('/api/estanterias/' . $estanteria->id)
            ->assertOk();

        $this->assertDatabaseMissing('estanterias', ['id' => $estanteria->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/estanterias/999999')
            ->assertNotFound();
    }
}
