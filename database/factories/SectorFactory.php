<?php

namespace Database\Factories;

use App\Models\Patio;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patio_id' => Patio::factory(),
            'codigo' => fake()->bothify('S-##'),
            'tipo_zona' => fake()->randomElement(['Turismos', 'Furgonetas', 'Pesados'])
        ];
    }
}
