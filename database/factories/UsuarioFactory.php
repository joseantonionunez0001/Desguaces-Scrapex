<?php

namespace Database\Factories;

use App\Models\Desguace;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsuarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rol_id' => Rol::factory(),
            'desguace_id' => Desguace::factory(),
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            // El cast 'hashed' del modelo cifra este valor al guardarlo
            'contrasena' => 'password'
        ];
    }
}
