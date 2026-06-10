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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained();
            $table->foreignId('materia_id')->constrained('materias');
            $table->date('fecha');
            $table->enum('estado', ['P', 'A', 'J'])->comment('P=Presente, A=Ausente, J=Justificado');
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['matricula_id', 'fecha', 'materia_id']);
            $table->index(['matricula_id', 'fecha', 'materia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
