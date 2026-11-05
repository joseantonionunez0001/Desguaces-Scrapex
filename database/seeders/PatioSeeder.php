<?php

namespace Database\Seeders;

use App\Models\Patio;
use Illuminate\Database\Seeder;

class PatioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requiere que la tabla `desguaces` exista (la factoría crea un desguace por patio).
     */
    public function run(): void
    {
        Patio::factory()->count(5)->create();
    }
}
