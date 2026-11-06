<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requiere que existan las tablas `roles` y `desguaces` (la factoría crea ambos registros).
     */
    public function run(): void
    {
        Usuario::factory()->count(5)->create();
    }
}
