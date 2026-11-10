<?php

namespace Database\Factories;

use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Pasillo>
 */
class PasilloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_modulo' => Modulo::factory(),
            'numero_pasillo' => (string) fake()->numberBetween(1, 20),
        ];
    }
}
