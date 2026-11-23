<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea el esquema con los formularios simplificados.
 *
 * - categorias.id_modulo pasa a ser opcional: el alta de categorías solo pide el nombre.
 *   La categoría sigue perteneciendo a un desguace (id_desguace), que asigna el servidor.
 * - tipo_piezas.fabricante y tipo_piezas.categoria (texto) pasan a ser opcionales. La relación
 *   del tipo con su categoría tiene una única fuente: id_categoria. La columna de texto se conserva
 *   con sus valores anteriores, pero ya no se usa.
 * - La marca y el modelo de tipo_piezas ya eran opcionales: ahora pertenecen a la pieza
 *   (ver add_modelo_vehiculo_to_piezas_table) y se conservan solo como dato histórico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_modulo')->nullable()->change();
        });

        Schema::table('tipo_piezas', function (Blueprint $table) {
            $table->string('fabricante')->nullable()->change();
            $table->string('categoria')->nullable()->change();
        });
    }

    /**
     * Vuelve a exigir los campos. Falla si ya hay registros sin ellos, para no inventar datos.
     */
    public function down(): void
    {
        Schema::table('tipo_piezas', function (Blueprint $table) {
            $table->string('fabricante')->nullable(false)->change();
            $table->string('categoria')->nullable(false)->change();
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_modulo')->nullable(false)->change();
        });
    }
};
