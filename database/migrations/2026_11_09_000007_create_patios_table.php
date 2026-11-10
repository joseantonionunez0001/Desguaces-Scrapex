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
        Schema::create('patios', function (Blueprint $table) {
            $table->id('id_patio');
            $table->foreignId('id_desguace')->constrained('desguaces', 'id_desguace')->restrictOnDelete();
            $table->string('codigo', 50);
            $table->string('nombre');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patios');
    }
};
