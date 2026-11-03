<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PasilloFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_pasillo' => (string) fake()->numberBetween(1, 99)
        ];
    }
}
