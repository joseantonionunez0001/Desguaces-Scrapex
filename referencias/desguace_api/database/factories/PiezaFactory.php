<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PiezaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement([
                'Alternador',
                'Motor de arranque',
                'Retrovisor',
                'Faro delantero',
                'Radiador',
                'Puerta'
            ]),
            'referencia' => fake()->unique()->bothify('REF-#####??'),
            'precio' => fake()->randomFloat(2, 10, 500),
            'stock' => fake()->numberBetween(1, 20)
        ];
    }
}