<?php

namespace Tests\Feature;

use App\Models\Estanteria;
use App\Models\Etiqueta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtiquetaApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    public function test_index_devuelve_las_etiquetas(): void
    {
        Etiqueta::factory()->count(2)->create();

        $this->getJson('/api/etiquetas')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_store_crea_una_etiqueta(): void
    {
        $estanteria = Estanteria::factory()->create();

        $this->postJson('/api/etiquetas', [
            'estanteria_id' => $estanteria->id,
            'numero_etiqueta' => 15,
        ])
            ->assertCreated()
            ->assertJsonPath('data.estanteria_id', $estanteria->id);
    }

    public function test_store_no_permite_repetir_numero_en_la_misma_estanteria(): void
    {
        $estanteria = Estanteria::factory()->create();
        Etiqueta::factory()->create(['estanteria_id' => $estanteria->id, 'numero_etiqueta' => 3]);

        $this->postJson('/api/etiquetas', [
            'estanteria_id' => $estanteria->id,
            'numero_etiqueta' => 3,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['numero_etiqueta']);
    }

    public function test_store_valida_una_estanteria_inexistente(): void
    {
        $this->postJson('/api/etiquetas', [
            'estanteria_id' => 999999,
            'numero_etiqueta' => 1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['estanteria_id']);
    }

    public function test_update_modifica_una_etiqueta(): void
    {
        $etiqueta = Etiqueta::factory()->create();

        $this->putJson('/api/etiquetas/'.$etiqueta->id, [
            'estanteria_id' => $etiqueta->estanteria_id,
            'numero_etiqueta' => 500,
        ])->assertOk()
            ->assertJsonPath('data.numero_etiqueta', 500);
    }

    public function test_destroy_elimina_una_etiqueta(): void
    {
        $etiqueta = Etiqueta::factory()->create();

        $this->deleteJson('/api/etiquetas/'.$etiqueta->id)
            ->assertOk();

        $this->assertDatabaseMissing('etiquetas', ['id' => $etiqueta->id]);
    }
}
