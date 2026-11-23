<?php

namespace Database\Factories;

use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modulo>
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
            'id_desguace' => fn (): int => DesguaceFactory::idPorDefecto(),
            'nombre_modulo' => 'Módulo '.fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
        ];
    }
}
