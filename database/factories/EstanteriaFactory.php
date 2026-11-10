<?php

namespace Database\Factories;

use App\Models\Pasillo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Estanteria>
 */
class EstanteriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pasillo' => Pasillo::factory(),
            'numero_estanteria' => (string) fake()->numberBetween(1, 30),
        ];
    }
}
