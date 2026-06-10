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
        Schema::create('reportes_estadisticos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->json('parametros');
            $table->string('tipo');
            $table->foreignId('generado_por')->constrained('users');
            $table->timestamp('generado_en')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_estadisticos');
    }
};
