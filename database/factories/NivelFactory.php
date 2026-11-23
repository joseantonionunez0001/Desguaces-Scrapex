<?php

namespace Database\Factories;

use App\Models\Posicion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Nivel>
 */
class NivelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_posicion' => Posicion::factory(),
            'numero_nivel' => (string) fake()->numberBetween(1, 5),
        ];
    }
}
