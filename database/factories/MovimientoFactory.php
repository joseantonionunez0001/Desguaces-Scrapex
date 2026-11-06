<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo_movimiento' => fake()->randomElement(['Entrada', 'Salida']),
            'fecha' => fake()->date(),
            'cantidad' => fake()->numberBetween(1, 100)
        ];
    }
}
