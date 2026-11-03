<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DesguaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Desguace ' . fake()->lastName(),
            'direccion' => fake()->streetAddress(),
            'numero_telefono' => fake()->numerify('6########'),
            'ubicacion' => fake()->city()
        ];
    }
}
