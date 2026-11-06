<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FilaFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Sin restricción de clave foránea hasta que exista la tabla sectores.
            'sector_id' => fake()->numberBetween(1, 99),
            'numero_fila' => (string) fake()->numberBetween(1, 99)
        ];
    }
}
