<?php

namespace Tests\Feature;

use App\Models\UbicacionVehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UbicacionVehiculoApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    public function test_index_devuelve_las_ubicaciones(): void
    {
        UbicacionVehiculo::factory()->count(2)->create();

        $this->getJson('/api/ubicaciones-vehiculo')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_store_crea_una_ubicacion_activa(): void
    {
        $this->postJson('/api/ubicaciones-vehiculo', [
            'vehiculo_id' => 900,
            'nivel_id' => 900,
            'fecha_inicio' => '2026-11-01 10:00:00',
        ])
            ->assertCreated()
            ->assertJsonPath('data.vehiculo_id', 900);
    }

    public function test_store_no_permite_dos_ubicaciones_activas_para_el_mismo_vehiculo(): void
    {
        UbicacionVehiculo::create([
            'vehiculo_id' => 901,
            'nivel_id' => 901,
            'fecha_inicio' => '2026-11-01 10:00:00',
            'fecha_fin' => null,
        ]);

        $this->postJson('/api/ubicaciones-vehiculo', [
            'vehiculo_id' => 901,
            'nivel_id' => 902,
            'fecha_inicio' => '2026-11-02 10:00:00',
        ])->assertStatus(422)
            ->assertJsonPath('success', 0);
    }

    public function test_store_no_permite_dos_vehiculos_activos_en_el_mismo_nivel(): void
    {
        UbicacionVehiculo::create([
            'vehiculo_id' => 911,
            'nivel_id' => 911,
            'fecha_inicio' => '2026-11-01 10:00:00',
            'fecha_fin' => null,
        ]);

        $this->postJson('/api/ubicaciones-vehiculo', [
            'vehiculo_id' => 912,
            'nivel_id' => 911,
            'fecha_inicio' => '2026-11-02 10:00:00',
        ])->assertStatus(422);
    }

    public function test_store_permite_registrar_historial_con_fecha_fin(): void
    {
        UbicacionVehiculo::create([
            'vehiculo_id' => 921,
            'nivel_id' => 921,
            'fecha_inicio' => '2026-11-01 10:00:00',
            'fecha_fin' => null,
        ]);

        $this->postJson('/api/ubicaciones-vehiculo', [
            'vehiculo_id' => 921,
            'nivel_id' => 922,
            'fecha_inicio' => '2026-10-01 10:00:00',
            'fecha_fin' => '2026-10-15 10:00:00',
        ])->assertCreated();
    }

    public function test_store_valida_que_fecha_fin_no_sea_anterior_a_fecha_inicio(): void
    {
        $this->postJson('/api/ubicaciones-vehiculo', [
            'vehiculo_id' => 931,
            'nivel_id' => 931,
            'fecha_inicio' => '2026-11-02 10:00:00',
            'fecha_fin' => '2026-11-01 10:00:00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_fin']);
    }

    public function test_destroy_elimina_una_ubicacion(): void
    {
        $ubicacion = UbicacionVehiculo::factory()->create();

        $this->deleteJson('/api/ubicaciones-vehiculo/'.$ubicacion->id)
            ->assertOk();

        $this->assertDatabaseMissing('ubicaciones_vehiculo', ['id' => $ubicacion->id]);
    }
}
