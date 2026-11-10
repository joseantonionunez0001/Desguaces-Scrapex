<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sin restricción unique en id_vehiculo ni en id_nivel: una posición
     * puede ocupar varios vehículos a lo largo del tiempo (historial).
     */
    public function up(): void
    {
        Schema::create('ubicaciones_vehiculo', function (Blueprint $table) {
            $table->id('id_ubicacion');
            $table->foreignId('id_vehiculo')->constrained('vehiculos', 'id_vehiculo')->restrictOnDelete();
            $table->foreignId('id_nivel')->constrained('niveles', 'id_nivel')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['id_nivel', 'fecha_inicio']);
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
