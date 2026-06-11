<?php

use App\Models\AuditoriaBodega;
use App\Models\DetalleAuditoriaBodega;
use App\Models\LoteAlimento;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ReporteEstadistico;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('usuario admin y director pueden ver las paginas de rendimiento, caracterizacion y estadisticos', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/reportes/rendimiento');
    $response->assertStatus(200);

    $response = $this->actingAs($director)->get('/reportes/caracterizacion');
    $response->assertStatus(200);

    $response = $this->actingAs($director)->get('/reportes/estadisticos');
    $response->assertStatus(200);
});

test('usuario bodega no puede ver rendimiento, caracterizacion ni estadisticos', function () {
    $bodega = User::factory()->create();
    $bodega->assignRole('bodega');

    $response = $this->actingAs($bodega)->get('/reportes/rendimiento');
    $response->assertStatus(403);

    $response = $this->actingAs($bodega)->get('/reportes/caracterizacion');
    $response->assertStatus(403);

    $response = $this->actingAs($bodega)->get('/reportes/estadisticos');
    $response->assertStatus(403);
});

test('usuario bodega, director y admin pueden ver la pagina de reporte de inventario', function () {
    $bodega = User::factory()->create();
    $bodega->assignRole('bodega');

    $response = $this->actingAs($bodega)->get('/reportes/inventario');
    $response->assertStatus(200);

    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/reportes/inventario');
    $response->assertStatus(200);
});

test('docente no puede ver ninguna de las paginas de reportes', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/reportes/rendimiento');
    $response->assertStatus(403);

    $response = $this->actingAs($docente)->get('/reportes/caracterizacion');
    $response->assertStatus(403);

    $response = $this->actingAs($docente)->get('/reportes/inventario');
    $response->assertStatus(403);

    $response = $this->actingAs($docente)->get('/reportes/estadisticos');
    $response->assertStatus(403);
});

test('puede guardar un reporte estadistico en la base de datos', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $reporte = ReporteEstadistico::create([
        'titulo' => 'Reporte Estadístico de Prueba',
        'tipo' => 'inventario',
        'parametros' => ['producto_id' => 1, 'fecha_desde' => '2026-06-01', 'fecha_hasta' => '2026-06-10'],
        'generado_por' => $admin->id,
    ]);

    expect($reporte->id)->not->toBeNull();
    expect($reporte->titulo)->toBe('Reporte Estadístico de Prueba');
    expect($reporte->parametros['producto_id'])->toBe(1);
});

test('el reporte de inventario incluye movimientos y auditorias en los limites del periodo', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $producto = Producto::create([
        'codigo' => 'PROD-R1',
        'nombre' => 'Azúcar',
        'tipo_embalaje' => 'Sacos',
        'unidad_peso' => 'kg',
        'peso_por_unidad' => 50.0,
        'activo' => true,
    ]);

    $lote = LoteAlimento::create([
        'codigo_lote' => 'LOTE-R1',
        'producto_id' => $producto->id,
        'fecha_ingreso' => '2026-06-01',
        'cantidad_autorizada' => 10,
        'unidades_completas' => 10,
        'unidades_fraccionadas' => 0,
        'peso_total_kg' => 500.0,
    ]);

    // Create a movement on the last day: 2026-06-10
    $mov = MovimientoInventario::create([
        'lote_id' => $lote->id,
        'tipo_movimiento' => 'entrada',
        'cantidad' => 20.0,
        'unidad' => 'kg',
        'fecha' => '2026-06-10',
        'observaciones' => 'Ajuste de prueba',
        'registrado_por' => $admin->id,
    ]);

    // Create a closed audit on the last day: 2026-06-10
    $aud = AuditoriaBodega::create([
        'fecha_auditoria' => '2026-06-10',
        'realizada_por' => $admin->id,
        'estado' => 'cerrada',
    ]);

    $det = DetalleAuditoriaBodega::create([
        'auditoria_id' => $aud->id,
        'lote_id' => $lote->id,
        'stock_sistema' => 500.0,
        'stock_fisico' => 480.0, // difference is -20.0
    ]);

    Livewire::actingAs($admin)
        ->test('pages::reportes.inventario')
        ->set('fechaDesde', '2026-06-01')
        ->set('fechaHasta', '2026-06-10')
        ->call('generar')
        ->assertSee('Azúcar')
        ->assertSee('+20.00 kg')
        ->assertSee('-20.00 kg');
});
