<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Registro de auditoría: sin timestamps ni softDeletes.
     */
    public function up(): void
    {
        Schema::create('registro_sistema', function (Blueprint $table) {
            $table->id('id_registro');
            $table->dateTime('fecha_hora');
            $table->foreignId('id_usuario')->constrained('users', 'id')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_sistema');
    }
};
