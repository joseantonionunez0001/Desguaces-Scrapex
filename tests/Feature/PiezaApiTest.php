<?php

namespace Tests\Feature;

use App\Models\Pieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PiezaApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    private array $datosValidos = [
        'nombre' => 'Puerta',
        'precio' => 45.5,
        'estado' => 'Usada',
        'stock' => 3,
        'stock_minimo' => 1,
    ];

    public function test_index_devuelve_las_piezas(): void
    {
        Pieza::factory()->count(2)->create();

        $this->getJson('/api/piezas')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_una_pieza(): void
    {
        $pieza = Pieza::factory()->create();

        $this->getJson('/api/piezas/'.$pieza->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $pieza->nombre);
    }

    public function test_store_crea_una_pieza(): void
    {
        $this->postJson('/api/piezas', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Puerta');

        $this->assertDatabaseHas('piezas', ['nombre' => 'Puerta', 'stock' => 3]);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/piezas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'precio', 'estado', 'stock', 'stock_minimo']);
    }

    public function test_store_rechaza_valores_negativos(): void
    {
        $this->postJson('/api/piezas', array_merge($this->datosValidos, ['precio' => -1, 'stock' => -2]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['precio', 'stock']);
    }

    public function test_update_modifica_una_pieza(): void
    {
        $pieza = Pieza::factory()->create();

        $this->putJson('/api/piezas/'.$pieza->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.estado', 'Usada');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $pieza = Pieza::factory()->create();

        $this->patchJson('/api/piezas/'.$pieza->id, ['stock' => 10])
            ->assertOk()
            ->assertJsonPath('data.stock', 10);
    }

    public function test_destroy_elimina_una_pieza(): void
    {
        $pieza = Pieza::factory()->create();

        $this->deleteJson('/api/piezas/'.$pieza->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('piezas', ['id' => $pieza->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/piezas/999999')
            ->assertNotFound();
    }
}
