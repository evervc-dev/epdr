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
        Schema::create('registro_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained();
            $table->foreignId('asignacion_docente_id')->constrained('asignaciones_docentes');
            $table->tinyInteger('trimestre')->unsigned();

            // Activities (35%)
            $table->decimal('act1', 4, 2)->nullable();
            $table->decimal('act2', 4, 2)->nullable();
            $table->decimal('act3', 4, 2)->nullable();
            $table->decimal('act4', 4, 2)->nullable();
            $table->decimal('act5', 4, 2)->nullable();
            $table->decimal('act6', 4, 2)->nullable();
            $table->decimal('act7', 4, 2)->nullable();
            $table->decimal('rev_cuaderno', 4, 2)->nullable();

            // Objective tests (30% & 35%)
            $table->decimal('prueba1', 4, 2)->nullable();
            $table->decimal('prueba2', 4, 2)->nullable();

            // Final Grade (calculated in model on saving)
            $table->decimal('nota_final', 4, 2)->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Indexes and Uniqueness
            $table->index('matricula_id', 'idx_notas_matricula');
            $table->unique(['matricula_id', 'asignacion_docente_id', 'trimestre']);
            $table->index(['asignacion_docente_id', 'trimestre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_notas');
    }
};
