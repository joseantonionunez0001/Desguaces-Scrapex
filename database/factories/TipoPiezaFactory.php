<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TipoPiezaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'categoria' => fake()->word(),
            'fabricante' => fake()->company()
        ];
    }
}
