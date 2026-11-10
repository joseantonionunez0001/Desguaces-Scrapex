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
        Schema::create('pasillos', function (Blueprint $table) {
            $table->id('id_pasillo');
            $table->foreignId('id_modulo')->constrained('modulos', 'id_modulo')->restrictOnDelete();
            $table->string('numero_pasillo', 50);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasillos');
    }
};
