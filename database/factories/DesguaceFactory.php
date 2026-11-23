<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desguace>
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

    /**
     * Desguace que usan por defecto las factories de inventario: el primero que exista o uno nuevo.
     *
     * Así los datos de prueba quedan en la misma sede salvo que se indique otra de forma explícita,
     * como ocurre en producción, donde cada registro pertenece a un único desguace.
     */
    public static function idPorDefecto(): int
    {
        $existente = Desguace::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('id_desguace')
            ->value('id_desguace');

        return (int) ($existente ?? Desguace::factory()->create()->getKey());
    }
}
