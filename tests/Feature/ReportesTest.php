<?php

use App\Models\ReporteEstadistico;
use App\Models\User;

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
