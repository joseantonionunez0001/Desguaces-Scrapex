<?php

namespace Database\Seeders;

use App\Models\UbicacionVehiculo;
use Illuminate\Database\Seeder;

class UbicacionVehiculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UbicacionVehiculo::factory()->count(5)->create();
    }
}
