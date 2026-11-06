<?php

namespace Tests\Feature;

use App\Models\RegistroSistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroSistemaApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    public function test_index_devuelve_los_registros(): void
    {
        RegistroSistema::factory()->count(2)->create();

        $this->getJson('/api/registros-sistema')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_store_crea_un_registro(): void
    {
        $this->postJson('/api/registros-sistema', ['fecha_hora' => '2026-11-03 09:30:00'])
            ->assertCreated();

        $this->assertDatabaseHas('registros_sistema', ['fecha_hora' => '2026-11-03 09:30:00']);
    }

    public function test_store_valida_la_fecha(): void
    {
        $this->postJson('/api/registros-sistema', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_hora']);
    }

    public function test_update_modifica_un_registro(): void
    {
        $registro = RegistroSistema::factory()->create();

        $this->putJson('/api/registros-sistema/'.$registro->id, ['fecha_hora' => '2026-01-01 00:00:00'])
            ->assertOk()
            ->assertJsonPath('success', 1);
    }

    public function test_destroy_elimina_un_registro(): void
    {
        $registro = RegistroSistema::factory()->create();

        $this->deleteJson('/api/registros-sistema/'.$registro->id)
            ->assertOk();

        $this->assertDatabaseMissing('registros_sistema', ['id' => $registro->id]);
    }
}
