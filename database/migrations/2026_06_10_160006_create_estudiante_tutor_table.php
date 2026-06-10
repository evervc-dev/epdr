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
        Schema::create('estudiante_tutor', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_familiar_id')->constrained('tutores_familiares')->cascadeOnDelete();
            $table->boolean('es_contacto_principal')->default(false);
            $table->primary(['estudiante_id', 'tutor_familiar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_tutor');
    }
};
