<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Existencias de cada pieza por hueco del almacén interior (etiqueta: módulo > pasillo > estantería > etiqueta).
 *
 * Una fila con id_etiqueta nulo significa «Pendiente de ubicar»: el stock que ya existía antes de esta
 * versión se guarda ahí, sin asignarle ningún hueco por suposición. piezas.stock sigue siendo el total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existencias_pieza', function (Blueprint $table) {
            $table->id('id_existencia');
            $table->foreignId('id_pieza')->constrained('piezas', 'id_pieza')->restrictOnDelete();
            $table->foreignId('id_etiqueta')->nullable()->constrained('etiquetas', 'id_etiqueta')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['id_pieza', 'id_etiqueta']);
        });

        $this->registrarStockAntiguoComoPendiente();
    }

    /**
     * Pasa el stock de cada pieza sin existencias a una fila «Pendiente de ubicar» con el mismo saldo.
     * Es idempotente: las piezas que ya tienen existencias no se tocan. Incluye las piezas archivadas.
     */
    public function registrarStockAntiguoComoPendiente(): void
    {
        $ahora = now();

        DB::table('piezas')
            ->where('stock', '>', 0)
            ->whereNotExists(fn ($consulta) => $consulta
                ->select(DB::raw(1))
                ->from('existencias_pieza')
                ->whereColumn('existencias_pieza.id_pieza', 'piezas.id_pieza'))
            ->orderBy('id_pieza')
            ->select(['id_pieza', 'stock'])
            ->chunk(500, function ($piezas) use ($ahora): void {
                DB::table('existencias_pieza')->insert($piezas->map(fn ($pieza): array => [
                    'id_pieza' => $pieza->id_pieza,
                    'id_etiqueta' => null,
                    'cantidad' => $pieza->stock,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('existencias_pieza');
    }
};
