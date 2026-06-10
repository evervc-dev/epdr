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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes_alimentos');
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'merma']);
            $table->decimal('cantidad', 10, 3);
            $table->string('unidad')->default('kg');
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->foreignId('auditoria_id')->nullable()->constrained('auditorias_bodega');
            $table->timestamps();

            $table->index(['lote_id', 'tipo_movimiento', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
