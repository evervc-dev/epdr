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
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('nie', 20)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->enum('genero', ['M', 'F']);

            $table->boolean('es_repitente')->default(false);
            $table->boolean('tiene_extraedad')->default(false);
            $table->boolean('pertenece_dai')->default(false);

            $table->enum('actividad_economica', [
                'No trabaja', 'Caña de azúcar', 'Pesca', 'Pepenador',
                'Trabajo doméstico', 'Cohetería', 'Café', 'Trabajos ambulantes',
                'Limpia autos/botas', 'Trabajos agrícolas', 'Otros',
            ])->default('No trabaja');

            $table->enum('convivencia', [
                'Vive con la madre', 'Vive con el padre', 'Vive con ambos',
                'Vive con familiares', 'No vive con familiares',
            ])->default('Vive con ambos');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
