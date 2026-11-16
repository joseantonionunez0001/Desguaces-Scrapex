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
        Schema::create('pieza_vehiculo', function (Blueprint $table) {
            $table->id();
             $table->foreignId('pieza_id')
                  ->constrained('piezas')
                  ->cascadeOnDelete();

            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->cascadeOnDelete();

            $table->string('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['pieza_id', 'vehiculo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieza_vehiculo');
    }
};
