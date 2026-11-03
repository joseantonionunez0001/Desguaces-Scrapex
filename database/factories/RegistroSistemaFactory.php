<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RegistroSistemaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha_hora' => fake()->dateTimeBetween('-1 month', 'now')
        ];
    }
}
