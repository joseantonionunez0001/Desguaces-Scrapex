<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculoApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    private array $datosValidos = [
        'matricula' => '1234ABC',
        'marca' => 'Seat',
        'modelo' => 'Leon',
        'fecha_llegada' => '2026-10-01',
        'numero_bastidor' => 'VIN123456789',
        'estado_tratamiento' => 'Pendiente',
    ];

    public function test_index_devuelve_los_vehiculos(): void
    {
        Vehiculo::factory()->count(2)->create();

        $this->getJson('/api/vehiculos')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->getJson('/api/vehiculos/'.$vehiculo->id)
            ->assertOk()
            ->assertJsonPath('data.matricula', $vehiculo->matricula);
    }

    public function test_store_crea_un_vehiculo(): void
    {
        $this->postJson('/api/vehiculos', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.matricula', '1234ABC');

        $this->assertDatabaseHas('vehiculos', ['matricula' => '1234ABC']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/vehiculos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matricula', 'marca', 'modelo', 'fecha_llegada', 'numero_bastidor', 'estado_tratamiento']);
    }

    public function test_update_modifica_un_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->putJson('/api/vehiculos/'.$vehiculo->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.marca', 'Seat');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->patchJson('/api/vehiculos/'.$vehiculo->id, ['estado_tratamiento' => 'Tratado'])
            ->assertOk()
            ->assertJsonPath('data.estado_tratamiento', 'Tratado');
    }

    public function test_destroy_elimina_un_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->deleteJson('/api/vehiculos/'.$vehiculo->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('vehiculos', ['id' => $vehiculo->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/vehiculos/999999')
            ->assertNotFound();
    }
}
