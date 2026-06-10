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
        Schema::create('tutores_familiares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('parentesco');
            $table->enum('nivel_academico', [
                'No sabe leer/escribir', 'Educación Básica',
                'Educación Media', 'Educación Superior',
            ]);
            $table->enum('situacion_laboral', [
                'Empleado', 'Comerciante', 'Oficios varios', 'No trabaja',
            ]);
            $table->string('telefono', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutores_familiares');
    }
};
