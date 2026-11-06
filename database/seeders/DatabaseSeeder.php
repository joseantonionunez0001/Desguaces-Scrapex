<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Rellena la base de datos con datos de prueba.
     *
     * Las factorías crean también sus dependencias (por ejemplo, un Desguace por Patio),
     * así que el orden no afecta a las claves foráneas.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            DesguaceSeeder::class,
            RolSeeder::class,
            UsuarioSeeder::class,
            PatioSeeder::class,
            SectorSeeder::class,
            ModuloSeeder::class,
            PasilloSeeder::class,
            FilaSeeder::class,
            NivelSeeder::class,
            PosicionSeeder::class,
            EstanteriaSeeder::class,
            EtiquetaSeeder::class,
            CategoriaSeeder::class,
            TipoPiezaSeeder::class,
            PiezaSeeder::class,
            VehiculoSeeder::class,
            UbicacionVehiculoSeeder::class,
            MovimientoSeeder::class,
            RegistroSistemaSeeder::class,
        ]);
    }
}
