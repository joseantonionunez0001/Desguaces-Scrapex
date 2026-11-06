<?php

namespace Database\Seeders;

use App\Models\TipoPieza;
use Illuminate\Database\Seeder;

class TipoPiezaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoPieza::factory()->count(5)->create();
    }
}
