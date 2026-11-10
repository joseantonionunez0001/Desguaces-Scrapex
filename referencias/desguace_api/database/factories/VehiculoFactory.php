<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehiculoFactory extends Factory
{
    public function definition(): array
    {
        $catalogo = [
            ['marca' => 'SEAT', 'modelo' => 'Ibiza'],
            ['marca' => 'Renault', 'modelo' => 'Clio'],
            ['marca' => 'Ford', 'modelo' => 'Focus'],
            ['marca' => 'Peugeot', 'modelo' => '308'],
            ['marca' => 'Volkswagen', 'modelo' => 'Golf']
        ];

        $elegido = fake()->randomElement($catalogo);

        return [
            'marca' => $elegido['marca'],
            'modelo' => $elegido['modelo'],
            'anio' => fake()->numberBetween(2005, 2023)
        ];
    }
}