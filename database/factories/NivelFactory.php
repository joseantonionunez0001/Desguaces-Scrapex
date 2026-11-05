<?php

namespace Database\Factories;

use App\Models\Posicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class NivelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'posicion_id' => Posicion::factory(),
            'numero_nivel' => (string) fake()->numberBetween(1, 10)
        ];
    }
}
