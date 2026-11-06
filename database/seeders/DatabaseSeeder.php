<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(50)->create();

        $user1 = User::factory()->create([
            'name' => 'Javier Ruiz',
            'email' => 'javier.ruiz@davante.es',
        ]);
        $user1->assignRole('administrador');

        $user2 = User::factory()->create([
            'name' => 'Alvaro Llorente',
            'email' => 'llorentebarataa@gmail.com',
        ]);
        $user2->assignRole('cliente');

        $user3 = User::factory()->create([
            'name' => 'Luis Reyes',
            'email' => 'luisralba06@gmail.com',
        ]);
        $user3->assignRole('encargado');
    }
}
