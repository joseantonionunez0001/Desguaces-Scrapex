<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EstanteriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_estanteria' => fake()->numberBetween(1, 99)
        ];
    }
}
