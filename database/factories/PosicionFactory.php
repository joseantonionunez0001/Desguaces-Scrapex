<?php

namespace Database\Factories;

use App\Models\Fila;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosicionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fila_id' => Fila::factory(),
            'numero_posicion' => (string) fake()->numberBetween(1, 99),
            'capacidad_maxima' => fake()->numberBetween(1, 10)
        ];
    }
}
