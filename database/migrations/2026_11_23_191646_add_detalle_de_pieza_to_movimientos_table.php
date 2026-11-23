<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada movimiento nuevo se registra sobre una pieza concreta, con el usuario, los huecos de origen
 * y destino, el saldo total de la pieza antes y después, el motivo (bajas) y una clave que impide
 * registrar dos veces el mismo envío del formulario.
 *
 * Todas las columnas nuevas son opcionales: los movimientos anteriores (por categoría y con tipo de
 * texto libre) se conservan tal cual como históricos. id_categoria se sigue rellenando con la categoría
 * de la pieza, que es la relación que determina el desguace del movimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('id_pieza')->nullable()->after('id_categoria')->constrained('piezas', 'id_pieza')->restrictOnDelete();
            $table->foreignId('id_usuario')->nullable()->after('id_pieza')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('id_etiqueta_origen')->nullable()->after('id_usuario')->constrained('etiquetas', 'id_etiqueta')->restrictOnDelete();
            $table->foreignId('id_etiqueta_destino')->nullable()->after('id_etiqueta_origen')->constrained('etiquetas', 'id_etiqueta')->restrictOnDelete();
            $table->unsignedInteger('saldo_anterior')->nullable()->after('cantidad');
            $table->unsignedInteger('saldo_posterior')->nullable()->after('saldo_anterior');
            $table->string('motivo', 500)->nullable()->after('saldo_posterior');
            $table->string('clave_envio', 64)->nullable()->unique()->after('motivo');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['id_pieza']);
            $table->dropForeign(['id_usuario']);
            $table->dropForeign(['id_etiqueta_origen']);
            $table->dropForeign(['id_etiqueta_destino']);
            $table->dropUnique(['clave_envio']);
            $table->dropColumn([
                'id_pieza',
                'id_usuario',
                'id_etiqueta_origen',
                'id_etiqueta_destino',
                'saldo_anterior',
                'saldo_posterior',
                'motivo',
                'clave_envio',
            ]);
        });
    }
};
