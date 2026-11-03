<?php

namespace Database\Seeders;

use App\Models\Desguace;
use Illuminate\Database\Seeder;

class DesguaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Desguace::factory()->count(5)->create();
    }
}
