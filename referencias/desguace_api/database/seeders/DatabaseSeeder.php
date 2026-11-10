<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use App\Models\Pieza;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        $vehiculos = Vehiculo::factory(8)->create();

        
        $piezas = Pieza::factory(12)->create();

       
        foreach ($vehiculos as $vehiculo) {

            $elegidas = $piezas->random(3);

            foreach ($elegidas as $pieza) {

                $vehiculo->piezas()->attach(
                    $pieza->id,
                    [
                        'observaciones' => fake()->randomElement([
                            'Comprobar referencia OEM',
                            'Verificar montaje',
                            'Consultar ficha tecnica'
                        ])
                    ]
                );
            }
        }
    }
}