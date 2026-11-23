<?php

namespace Database\Factories;

use App\Models\Marca;
use App\Models\ModeloVehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloVehiculo>
 */
class ModeloVehiculoFactory extends Factory
{
    protected $model = ModeloVehiculo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_marca' => Marca::factory(),
            'nombre' => fake()->unique()->bothify('Modelo ##??'),
        ];
    }
}
