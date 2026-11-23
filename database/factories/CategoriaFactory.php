<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Motor', 'Carrocería', 'Interior', 'Transmisión', 'Suspensión', 'Electrónica', 'Iluminación', 'Frenos']),
            'id_desguace' => fn (): int => DesguaceFactory::idPorDefecto(),
            'id_modulo' => Modulo::factory(),
            'id_vehiculo' => null,
        ];
    }
}
