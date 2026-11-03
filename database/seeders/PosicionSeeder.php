<?php

namespace Database\Seeders;

use App\Models\Posicion;
use Illuminate\Database\Seeder;

class PosicionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Posicion::factory()->count(5)->create();
    }
}
