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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id('id_vehiculo');
            $table->foreignId('id_desguace')->constrained('desguaces', 'id_desguace')->restrictOnDelete();
            $table->string('matricula', 20)->unique();
            $table->string('marca');
            $table->string('modelo');
            $table->date('fecha_llegada');
            $table->string('numero_bastidor', 17)->unique();
            $table->string('estado_tratamiento', 100);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
