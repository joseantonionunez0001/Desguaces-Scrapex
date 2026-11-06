<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PiezaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'precio' => fake()->randomFloat(2, 1, 500),
            'estado' => fake()->randomElement(['Nueva', 'Usada', 'Defectuosa']),
            'stock' => fake()->numberBetween(0, 50),
            'stock_minimo' => fake()->numberBetween(0, 10)
        ];
    }
}
