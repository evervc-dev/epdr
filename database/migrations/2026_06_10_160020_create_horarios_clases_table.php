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
        Schema::create('horarios_clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_docente_id')->constrained('asignaciones_docentes')->cascadeOnDelete();
            $table->tinyInteger('dia_semana')->unsigned()->comment('1=Lunes, 2=Martes, 3=Miercoles, 4=Jueves, 5=Viernes');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['asignacion_docente_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_clases');
    }
};
