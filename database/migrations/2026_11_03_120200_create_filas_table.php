<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filas', function (Blueprint $table) {
            $table->id();
            // Referencia a sectores. La restricción de clave foránea se añadirá
            // en una migración posterior, cuando exista la tabla sectores.
            $table->unsignedBigInteger('sector_id');
            $table->string('numero_fila', 20);
            $table->timestamps();

            $table->index('sector_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filas');
    }
};
