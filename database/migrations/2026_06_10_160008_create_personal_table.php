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
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('dui', 10)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->enum('genero', ['M', 'F']);
            $table->string('telefono', 20)->nullable();
            $table->string('correo')->nullable();
            $table->enum('tipo', ['docente', 'administrativo', 'servicio']);
            $table->string('especialidad')->nullable();
            $table->date('fecha_ingreso');
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
