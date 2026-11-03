<?php

namespace Database\Factories;

use App\Models\Estanteria;
use Illuminate\Database\Eloquent\Factories\Factory;

class EtiquetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estanteria_id' => Estanteria::factory(),
            // unique() evita chocar con la restricción estanteria_id + numero_etiqueta
            'numero_etiqueta' => fake()->unique()->numberBetween(1, 999)
        ];
    }
}
