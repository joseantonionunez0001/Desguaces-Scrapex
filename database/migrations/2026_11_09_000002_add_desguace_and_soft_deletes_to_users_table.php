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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_desguace')->nullable();
            $table->foreign('id_desguace')
                ->references('id_desguace')
                ->on('desguaces')
                ->restrictOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_desguace']);
            $table->dropColumn('id_desguace');
            $table->dropSoftDeletes();
        });
    }
};
