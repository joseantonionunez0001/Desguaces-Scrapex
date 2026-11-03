<?php

namespace Database\Seeders;

use App\Models\Pieza;
use Illuminate\Database\Seeder;

class PiezaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pieza::factory()->count(5)->create();
    }
}
