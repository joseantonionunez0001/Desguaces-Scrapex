<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehiculoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricula' => strtoupper(fake()->bothify('####???')),
            'marca' => fake()->randomElement(['Seat', 'Renault', 'Ford', 'Opel', 'Volkswagen', 'Toyota']),
            'modelo' => fake()->word(),
            'fecha_llegada' => fake()->date(),
            'numero_bastidor' => strtoupper(fake()->bothify('?????????????????')),
            'estado_tratamiento' => fake()->randomElement(['Pendiente', 'En tratamiento', 'Tratado'])
        ];
    }
}
