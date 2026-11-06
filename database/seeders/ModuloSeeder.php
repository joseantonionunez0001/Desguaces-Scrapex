<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requiere que la tabla `desguaces` exista (la factoría crea un desguace por módulo).
     */
    public function run(): void
    {
        Modulo::factory()->count(5)->create();
    }
}
