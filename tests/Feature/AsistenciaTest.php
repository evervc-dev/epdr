<?php

use App\Models\AnoLectivo;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('puede filtrar reportes de asistencia por materia', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();

    $seccion = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'Z',
        'turno' => 'Mañana',
    ]);

    $materia1 = Materia::create([
        'nombre' => 'Materia Test A',
        'codigo' => 'MTA01',
        'grado_id' => $grado->id,
    ]);

    $materia2 = Materia::create([
        'nombre' => 'Materia Test B',
        'codigo' => 'MTB01',
        'grado_id' => $grado->id,
    ]);

    $estudiante = Estudiante::create([
        'nie' => '99999999',
        'nombres' => 'Pedro',
        'apellidos' => 'Pérez',
        'fecha_nacimiento' => '2015-05-05',
        'genero' => 'M',
    ]);

    $matricula = Matricula::create([
        'estudiante_id' => $estudiante->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
        'tipo_inscripcion' => 'N',
        'fecha_matricula' => '2026-06-01',
        'estado' => 'ACTIVA',
    ]);

    // Create attendance: Present in Materia A, Absent in Materia B
    Asistencia::create([
        'matricula_id' => $matricula->id,
        'materia_id' => $materia1->id,
        'fecha' => '2026-06-10',
        'estado' => 'P',
        'registrado_por' => $admin->id,
    ]);

    Asistencia::create([
        'matricula_id' => $matricula->id,
        'materia_id' => $materia2->id,
        'fecha' => '2026-06-10',
        'estado' => 'A',
        'registrado_por' => $admin->id,
    ]);

    // Test without filtering (both present/absent counted)
    Livewire::actingAs($admin)
        ->test('pages::asistencias.reporte')
        ->set('seccionId', $seccion->id)
        ->set('materiaId', null)
        ->set('fechaDesde', '2026-06-10')
        ->set('fechaHasta', '2026-06-10')
        ->call('generar')
        ->assertSet('resumen.0.presentes', 1)
        ->assertSet('resumen.0.ausentes', 1);

    // Test filtering by Materia A (should show 1 present, 0 absent)
    Livewire::actingAs($admin)
        ->test('pages::asistencias.reporte')
        ->set('seccionId', $seccion->id)
        ->set('materiaId', $materia1->id)
        ->set('fechaDesde', '2026-06-10')
        ->set('fechaHasta', '2026-06-10')
        ->call('generar')
        ->assertSet('resumen.0.presentes', 1)
        ->assertSet('resumen.0.ausentes', 0);

    // Test filtering by Materia B (should show 0 present, 1 absent)
    Livewire::actingAs($admin)
        ->test('pages::asistencias.reporte')
        ->set('seccionId', $seccion->id)
        ->set('materiaId', $materia2->id)
        ->set('fechaDesde', '2026-06-10')
        ->set('fechaHasta', '2026-06-10')
        ->call('generar')
        ->assertSet('resumen.0.presentes', 0)
        ->assertSet('resumen.0.ausentes', 1);
});
