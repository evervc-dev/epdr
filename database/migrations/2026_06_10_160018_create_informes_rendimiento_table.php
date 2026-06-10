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
        Schema::create('informes_rendimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones');
            $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
            $table->tinyInteger('trimestre')->unsigned()->nullable();
            $table->integer('matricula_inicial')->default(0);
            $table->integer('matricula_actual')->default(0);
            $table->integer('aprobados_m')->default(0);
            $table->integer('aprobados_f')->default(0);
            $table->integer('reprobados_m')->default(0);
            $table->integer('reprobados_f')->default(0);
            $table->integer('desertores')->default(0);
            $table->integer('sobredad')->default(0);
            $table->integer('repitentes')->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('generado_por')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informes_rendimiento');
    }
};
