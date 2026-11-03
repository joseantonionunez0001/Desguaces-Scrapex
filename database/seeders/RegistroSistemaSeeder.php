<?php

namespace Database\Seeders;

use App\Models\RegistroSistema;
use Illuminate\Database\Seeder;

class RegistroSistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RegistroSistema::factory()->count(10)->create();
    }
}
