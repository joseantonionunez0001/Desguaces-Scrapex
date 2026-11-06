<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requiere que la tabla `patios` exista (la factoría crea un patio por sector).
     */
    public function run(): void
    {
        Sector::factory()->count(5)->create();
    }
}
