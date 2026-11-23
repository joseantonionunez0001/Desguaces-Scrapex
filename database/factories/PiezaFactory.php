<?php

namespace Database\Factories;

use App\Models\ExistenciaPieza;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pieza>
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
            'estado' => fake()->randomElement(Pieza::ESTADOS_FISICOS),
            'stock' => fake()->numberBetween(0, 20),
            'stock_minimo' => fake()->numberBetween(1, 3),
            'id_usuario' => User::factory(),
            'id_tipo_pieza' => TipoPieza::factory(),
            'id_modelo_vehiculo' => null,
        ];
    }

    /**
     * Igual que la migración con los datos anteriores: el stock creado directamente (sin movimientos)
     * queda como «Pendiente de ubicar», de modo que existencias y saldo siempre cuadran.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Pieza $pieza): void {
            if ($pieza->stock > 0 && ExistenciaPieza::query()->withoutGlobalScopes()->where('id_pieza', $pieza->getKey())->doesntExist()) {
                ExistenciaPieza::query()->withoutGlobalScopes()->create([
                    'id_pieza' => $pieza->getKey(),
                    'id_etiqueta' => null,
                    'cantidad' => $pieza->stock,
                ]);
            }
        });
    }
}
