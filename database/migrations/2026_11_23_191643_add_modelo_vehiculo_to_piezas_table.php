<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La pieza guarda el modelo de vehículo del que procede (y, a través de él, su marca).
 *
 * Es opcional para no inventar datos en las piezas antiguas: solo se rellena cuando su tipo de pieza
 * ya tenía marca y modelo registrados (los formularios anteriores guardaban ahí esos datos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piezas', function (Blueprint $table) {
            $table->foreignId('id_modelo_vehiculo')
                ->nullable()
                ->after('id_tipo_pieza')
                ->constrained('modelos_vehiculo', 'id_modelo')
                ->restrictOnDelete();
        });

        $tiposConModelo = DB::table('tipo_piezas')
            ->whereNotNull('marca')
            ->whereNotNull('modelo')
            ->get(['id_pieza', 'marca', 'modelo']);

        foreach ($tiposConModelo as $tipo) {
            $idModelo = DB::table('modelos_vehiculo')
                ->join('marcas', 'marcas.id_marca', '=', 'modelos_vehiculo.id_marca')
                ->where('marcas.nombre', trim($tipo->marca))
                ->where('modelos_vehiculo.nombre', trim($tipo->modelo))
                ->value('modelos_vehiculo.id_modelo');

            if ($idModelo !== null) {
                DB::table('piezas')
                    ->where('id_tipo_pieza', $tipo->id_pieza)
                    ->whereNull('id_modelo_vehiculo')
                    ->update(['id_modelo_vehiculo' => $idModelo]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('piezas', function (Blueprint $table) {
            $table->dropForeign(['id_modelo_vehiculo']);
            $table->dropColumn('id_modelo_vehiculo');
        });
    }
};
