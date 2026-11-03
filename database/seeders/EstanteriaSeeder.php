<?php

namespace Database\Seeders;

use App\Models\Estanteria;
use Illuminate\Database\Seeder;

class EstanteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estanteria::factory()->count(5)->create();
    }
}
