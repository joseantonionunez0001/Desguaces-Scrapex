<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuloFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_modulo' => 'Modulo ' . fake()->bothify('??-##'),
            'desguace_id' => Desguace::factory()
        ];
    }
}
