<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de marcas y modelos de vehículo: es la única fuente para validar que el modelo
 * elegido en una pieza corresponde a su marca.
 *
 * Se rellena con los pares marca/modelo que ya existen en vehículos y tipos de pieza;
 * no se borra ni se modifica ningún registro anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id('id_marca');
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        Schema::create('modelos_vehiculo', function (Blueprint $table) {
            $table->id('id_modelo');
            $table->foreignId('id_marca')->constrained('marcas', 'id_marca')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['id_marca', 'nombre']);
        });

        $pares = DB::table('vehiculos')->select('marca', 'modelo')
            ->union(DB::table('tipo_piezas')->select('marca', 'modelo'))
            ->get();

        $ahora = now();

        foreach ($pares as $par) {
            $marca = trim((string) $par->marca);
            $modelo = trim((string) $par->modelo);

            if ($marca === '') {
                continue;
            }

            $idMarca = DB::table('marcas')->where('nombre', $marca)->value('id_marca')
                ?? DB::table('marcas')->insertGetId(['nombre' => $marca, 'created_at' => $ahora, 'updated_at' => $ahora], 'id_marca');

            if ($modelo !== '' && DB::table('modelos_vehiculo')->where('id_marca', $idMarca)->where('nombre', $modelo)->doesntExist()) {
                DB::table('modelos_vehiculo')->insert([
                    'id_marca' => $idMarca,
                    'nombre' => $modelo,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_vehiculo');
        Schema::dropIfExists('marcas');
    }
};
