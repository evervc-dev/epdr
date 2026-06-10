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
        Schema::create('detalle_auditorias_bodega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_id')->constrained('auditorias_bodega')->cascadeOnDelete();
            $table->foreignId('lote_id')->constrained('lotes_alimentos');
            $table->decimal('stock_sistema', 10, 3);
            $table->decimal('stock_fisico', 10, 3);
            $table->decimal('diferencia', 10, 3)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_auditorias_bodega');
    }
};
