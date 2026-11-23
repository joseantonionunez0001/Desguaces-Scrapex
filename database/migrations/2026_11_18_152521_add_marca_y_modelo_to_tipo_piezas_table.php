<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nulables para que las filas existentes no fallen al migrar. El formulario web exige ambos campos.
     */
    public function up(): void
    {
        Schema::table('tipo_piezas', function (Blueprint $table) {
            $table->string('marca', 255)->nullable()->after('fabricante');
            $table->string('modelo', 255)->nullable()->after('marca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_piezas', function (Blueprint $table) {
            $table->dropColumn(['marca', 'modelo']);
        });
    }
};
