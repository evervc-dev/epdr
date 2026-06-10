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
        Schema::create('lotes_alimentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_lote', 30)->unique();
            $table->foreignId('producto_id')->constrained();
            $table->date('fecha_ingreso');
            $table->integer('cantidad_autorizada');
            $table->integer('unidades_completas')->default(0);
            $table->decimal('unidades_fraccionadas', 8, 3)->default(0);
            $table->decimal('peso_total_kg', 10, 3)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_alimentos');
    }
};
