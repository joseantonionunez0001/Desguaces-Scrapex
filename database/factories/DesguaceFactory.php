<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Desguace>
 */
class DesguaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Desguace '.fake()->randomElement(['Norte', 'Sur', 'Este', 'Oeste', 'Central', 'Levante']),
            'direccion' => fake()->randomElement(['Calle Mayor', 'Avenida de la Industria', 'Calle del Progreso', 'Calle de los Talleres']).', '.fake()->buildingNumber(),
            'numero_telefono' => fake()->numerify('9########'),
            'ubicación' => fake()->randomElement(['Polígono Industrial Los Olivos', 'Polígono El Pla', 'Zona Franca Norte', 'Carretera N-IV, km 12']),
        ];
    }
}
