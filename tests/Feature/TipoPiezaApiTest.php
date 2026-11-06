<?php

namespace Tests\Feature;

use App\Models\TipoPieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoPiezaApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    private array $datosValidos = [
        'nombre' => 'Puerta delantera',
        'categoria' => 'Carroceria',
        'fabricante' => 'Seat',
    ];

    public function test_index_devuelve_los_tipos_de_pieza(): void
    {
        TipoPieza::factory()->count(2)->create();

        $this->getJson('/api/tipo_piezas')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_tipo_de_pieza(): void
    {
        $tipoPieza = TipoPieza::factory()->create();

        $this->getJson('/api/tipo_piezas/'.$tipoPieza->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $tipoPieza->nombre);
    }

    public function test_store_crea_un_tipo_de_pieza(): void
    {
        $this->postJson('/api/tipo_piezas', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Puerta delantera');

        $this->assertDatabaseHas('tipo_piezas', ['nombre' => 'Puerta delantera']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/tipo_piezas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'categoria', 'fabricante']);
    }

    public function test_update_modifica_un_tipo_de_pieza(): void
    {
        $tipoPieza = TipoPieza::factory()->create();

        $this->putJson('/api/tipo_piezas/'.$tipoPieza->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.fabricante', 'Seat');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $tipoPieza = TipoPieza::factory()->create();

        $this->patchJson('/api/tipo_piezas/'.$tipoPieza->id, ['fabricante' => 'Renault'])
            ->assertOk()
            ->assertJsonPath('data.fabricante', 'Renault');
    }

    public function test_destroy_elimina_un_tipo_de_pieza(): void
    {
        $tipoPieza = TipoPieza::factory()->create();

        $this->deleteJson('/api/tipo_piezas/'.$tipoPieza->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('tipo_piezas', ['id' => $tipoPieza->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/tipo_piezas/999999')
            ->assertNotFound();
    }
}
