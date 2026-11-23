<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Patio>
 */
class PatioFactory extends Factory
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
            'codigo' => fake()->bothify('PAT-##'),
            'nombre' => 'Patio '.fake()->randomElement(['Norte', 'Sur', 'Central', 'Exterior']),
        ];
    }
}
