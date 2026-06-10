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
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained();
            $table->foreignId('seccion_id')->constrained('secciones');
            $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
            $table->enum('tipo_inscripcion', ['V', 'N', 'T'])->comment('V=Variable, N=Nuevo, T=Traslado');
            $table->date('fecha_matricula');
            $table->enum('estado', ['ACTIVA', 'RETIRADA', 'TRASLADADA'])->default('ACTIVA');
            $table->timestamps();

            // Indexes and Uniqueness
            $table->index('estudiante_id', 'idx_matriculas_estudiante');
            $table->unique(['estudiante_id', 'seccion_id', 'ano_lectivo_id']);
            $table->index(['ano_lectivo_id', 'seccion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
