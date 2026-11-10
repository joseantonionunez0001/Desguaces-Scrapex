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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('categoria_id');
            $table->string('nombre');
            $table->foreignId('id_desguace')->constrained('desguaces', 'id_desguace')->restrictOnDelete();
            $table->foreignId('id_modulo')->constrained('modulos', 'id_modulo')->restrictOnDelete();
            $table->foreignId('id_vehiculo')->nullable()->constrained('vehiculos', 'id_vehiculo')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
