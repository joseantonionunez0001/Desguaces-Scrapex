<?php

namespace Tests\Feature;

use App\Models\Movimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimientoApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    private array $datosValidos = [
        'tipo_movimiento' => 'Entrada',
        'fecha' => '2026-10-01',
        'cantidad' => 5,
    ];

    public function test_index_devuelve_los_movimientos(): void
    {
        Movimiento::factory()->count(2)->create();

        $this->getJson('/api/movimientos')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_movimiento(): void
    {
        $movimiento = Movimiento::factory()->create();

        $this->getJson('/api/movimientos/'.$movimiento->id)
            ->assertOk()
            ->assertJsonPath('data.tipo_movimiento', $movimiento->tipo_movimiento);
    }

    public function test_store_crea_un_movimiento(): void
    {
        $this->postJson('/api/movimientos', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.tipo_movimiento', 'Entrada');

        $this->assertDatabaseHas('movimientos', ['tipo_movimiento' => 'Entrada', 'cantidad' => 5]);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/movimientos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_movimiento', 'fecha', 'cantidad']);
    }

    public function test_update_modifica_un_movimiento(): void
    {
        $movimiento = Movimiento::factory()->create();

        $this->putJson('/api/movimientos/'.$movimiento->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.cantidad', 5);
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $movimiento = Movimiento::factory()->create();

        $this->patchJson('/api/movimientos/'.$movimiento->id, ['tipo_movimiento' => 'Salida'])
            ->assertOk()
            ->assertJsonPath('data.tipo_movimiento', 'Salida');
    }

    public function test_destroy_elimina_un_movimiento(): void
    {
        $movimiento = Movimiento::factory()->create();

        $this->deleteJson('/api/movimientos/'.$movimiento->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('movimientos', ['id' => $movimiento->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/movimientos/999999')
            ->assertNotFound();
    }
}
