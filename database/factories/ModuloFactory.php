<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Modulo>
 */
class ModuloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_desguace' => Desguace::factory(),
            'nombre_modulo' => 'Módulo '.fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
        ];
    }
}
