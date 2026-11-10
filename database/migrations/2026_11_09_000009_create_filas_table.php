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
            $table->id('id_fila');
            $table->foreignId('id_sector')->constrained('sectores', 'id_sector')->restrictOnDelete();
            $table->string('numero_fila', 50);
            $table->softDeletes();
            $table->timestamps();
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
