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
        Schema::create('estanterias', function (Blueprint $table) {
            $table->id();
            // Relación Pasillo (1) -> Estanteria (N). La clave foránea se añadirá
            // cuando exista la tabla pasillos.
            $table->unsignedBigInteger('pasillo_id')->nullable()->index();
            $table->unsignedInteger('numero_estanteria');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estanterias');
    }
};
