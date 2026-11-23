<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\TipoPieza;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoPieza>
 */
class TipoPiezaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TipoPieza>
     */
    protected $model = TipoPieza::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Alternador', 'Puerta delantera', 'Faro izquierdo', 'Motor de arranque', 'Radiador', 'Retrovisor derecho', 'Intermitente trasero', 'Caja de cambios']),
            'fabricante' => fake()->randomElement(['Autopartes Levante S.L.', 'Componentes Meseta S.A.', 'Recambios Norte S.L.', 'Industrias Cantábrica S.A.', 'Piezas Ibéricas S.L.']),
            'categoria' => fake()->randomElement(['Motor', 'Carrocería', 'Iluminación', 'Transmisión', 'Suspensión', 'Electricidad']),
            'id_categoria' => Categoria::factory(),
        ];
    }
}
