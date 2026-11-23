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
        Schema::create('piezas', function (Blueprint $table) {
            $table->id('id_pieza');
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->string('estado', 50);
            $table->unsignedInteger('stock');
            $table->unsignedInteger('stock_minimo');
            $table->foreignId('id_usuario')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('id_tipo_pieza')->constrained('tipo_piezas', 'id_pieza')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('piezas');
    }
};
