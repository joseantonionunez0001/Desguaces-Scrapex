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
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            // Relación Estanteria (1) -> Etiqueta (N)
            $table->foreignId('estanteria_id')->constrained('estanterias');
            $table->unsignedInteger('numero_etiqueta');
            $table->timestamps();

            // Una estantería no puede tener dos etiquetas con el mismo número
            $table->unique(['estanteria_id', 'numero_etiqueta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas');
    }
};
