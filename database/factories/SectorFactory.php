<?php

namespace Database\Factories;

use App\Models\Patio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Sector>
 */
class SectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_patio' => Patio::factory(),
            'codigo' => fake()->bothify('S-##'),
            'tipo_zona' => fake()->randomElement(['Carrocerías', 'Motores y cajas', 'Interiores', 'Pequeñas piezas', 'Neumáticos', 'Chatarra']),
        ];
    }
}
