<?php

namespace Database\Factories;

use App\Models\Fila;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Posicion>
 */
class PosicionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_fila' => Fila::factory(),
            'numero_posicion' => (string) fake()->numberBetween(1, 30),
            'capacidad_maxima' => fake()->numberBetween(1, 6),
        ];
    }
}
