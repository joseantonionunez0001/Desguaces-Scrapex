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
        Schema::create('desguaces', function (Blueprint $table) {
            $table->id('id_desguace');
            $table->string('nombre');
            $table->string('direccion');
            $table->string('numero_telefono', 20);
            $table->string('ubicación');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desguaces');
    }
};
