<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'desguace_id' => Desguace::factory(),
            'codigo' => fake()->bothify('P-##'),
            'nombre' => 'Patio ' . fake()->lastName()
        ];
    }
}
