<?php

namespace Database\Seeders;

use App\Models\Fila;
use Illuminate\Database\Seeder;

class FilaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Fila::factory()->count(5)->create();
    }
}
