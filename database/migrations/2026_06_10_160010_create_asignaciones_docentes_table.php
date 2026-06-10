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
        Schema::create('asignaciones_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personal');
            $table->foreignId('materia_id')->constrained();
            $table->foreignId('seccion_id')->constrained('secciones');
            $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
            $table->timestamps();

            $table->unique(['personal_id', 'materia_id', 'seccion_id', 'ano_lectivo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_docentes');
    }
};
