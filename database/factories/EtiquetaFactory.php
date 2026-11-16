<?php

namespace Database\Factories;

use App\Models\Estanteria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Etiqueta>
 */
class EtiquetaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_estanteria' => Estanteria::factory(),
            'numero_etiqueta' => fake()->numerify('ET-####'),
        ];
    }
}
