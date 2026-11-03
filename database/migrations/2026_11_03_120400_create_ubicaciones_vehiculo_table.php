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
        Schema::create('ubicaciones_vehiculo', function (Blueprint $table) {
            $table->id();
            // Relaciones Vehiculo (1) -> Ubicacion_Vehiculo (N) y Nivel (1) -> Ubicacion_Vehiculo (N).
            // Las claves foráneas se añadirán cuando existan las tablas vehiculos y niveles.
            $table->unsignedBigInteger('vehiculo_id')->index();
            $table->unsignedBigInteger('nivel_id')->index();
            $table->dateTime('fecha_inicio');
            // Null = ubicación activa
            $table->dateTime('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_vehiculo');
    }
};
