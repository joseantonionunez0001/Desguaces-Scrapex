<?php

namespace Database\Seeders;

use App\Models\Pasillo;
use Illuminate\Database\Seeder;

class PasilloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pasillo::factory()->count(5)->create();
    }
}
