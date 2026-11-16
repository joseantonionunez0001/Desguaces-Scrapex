<?php

namespace Database\Factories;

use App\Models\TipoPieza;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Pieza>
 */
class PiezaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Alternador', 'Puerta delantera', 'Faro izquierdo', 'Motor de arranque', 'Radiador', 'Retrovisor derecho', 'Intermitente trasero', 'Caja de cambios']),
            'precio' => fake()->randomFloat(2, 5, 800),
            'estado' => fake()->randomElement(['Nueva', 'Usada', 'Reacondicionada']),
            'stock' => fake()->numberBetween(0, 20),
            'stock_minimo' => fake()->numberBetween(1, 3),
            'id_usuario' => User::factory(),
            'id_tipo_pieza' => TipoPieza::factory(),
        ];
    }
}
