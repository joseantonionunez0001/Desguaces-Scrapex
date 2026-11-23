<?php

namespace Database\Factories;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marca>
 */
class MarcaFactory extends Factory
{
    protected $model = Marca::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Nombre propio de las pruebas: no choca con las marcas que se registran al crear vehículos.
            'nombre' => 'Marca '.fake()->unique()->bothify('??##'),
        ];
    }
}
