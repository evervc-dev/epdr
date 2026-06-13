<?php

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Personal;
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

test('puede guardar asistencia dos veces sin error de clave unica', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    // Let's create an assignment with unique code and personal info
    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();
    $seccion = Seccion::where('grado_id', $grado->id)->first() ?: Seccion::create(['grado_id' => $grado->id, 'ano_lectivo_id' => $ano->id, 'letra' => 'X', 'turno' => 'Mañana']);
    $materia = Materia::create(['nombre' => 'Matemática Test', 'codigo' => 'MAT_TEST_'.uniqid(), 'grado_id' => $grado->id]);
    $docente = Personal::create([
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'dui' => '12345678-'.rand(0, 9),
        'fecha_nacimiento' => '1980-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);
    $asignacion = AsignacionDocente::create([
        'ano_lectivo_id' => $ano->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'personal_id' => $docente->id,
    ]);

    // Create a schedule for this assignment so it passes validation
    \App\Models\HorarioClase::create([
        'asignacion_docente_id' => $asignacion->id,
        'dia_semana' => 4, // Thursday
        'hora_inicio' => '07:00:00',
        'hora_fin' => '08:30:00',
    ]);

    // Ensure there is at least one student matriculado in this section
    $matricula = Matricula::where('seccion_id', $seccion->id)->first();
    if (! $matricula) {
        $estudiante = Estudiante::create([
            'nie' => '1234567',
            'nombres' => 'Juan',
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
    }

    // Now test the Livewire component: register attendance once, then register again
    $fecha = '2026-06-11';
    Livewire::actingAs($admin)
        ->test('pages::asistencias.index')
        ->set('asignacionId', $asignacion->id)
        ->set('fecha', $fecha)
        ->call('guardar') // First save
        ->call('guardar') // Second save
        ->assertHasNoErrors();

    expect(Asistencia::where('materia_id', $materia->id)->count())->toBeGreaterThan(0);
});

test('no puede guardar asistencia los fines de semana', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();
    $seccion = Seccion::where('grado_id', $grado->id)->first() ?: Seccion::create(['grado_id' => $grado->id, 'ano_lectivo_id' => $ano->id, 'letra' => 'X', 'turno' => 'Mañana']);
    $materia = Materia::create(['nombre' => 'Materia Finde', 'codigo' => 'MAT_FINDE_'.uniqid(), 'grado_id' => $grado->id]);
    $docente = Personal::create([
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'dui' => '12345678-'.rand(0, 9),
        'fecha_nacimiento' => '1980-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);
    $asignacion = AsignacionDocente::create([
        'ano_lectivo_id' => $ano->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'personal_id' => $docente->id,
    ]);

    // Ensure there is at least one student
    $matricula = Matricula::where('seccion_id', $seccion->id)->first();
    if (! $matricula) {
        $estudiante = Estudiante::create([
            'nie' => '1234568',
            'nombres' => 'María',
            'apellidos' => 'Gómez',
            'fecha_nacimiento' => '2015-05-05',
            'genero' => 'F',
        ]);
        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'seccion_id' => $seccion->id,
            'ano_lectivo_id' => $ano->id,
            'tipo_inscripcion' => 'N',
            'fecha_matricula' => '2026-06-01',
            'estado' => 'ACTIVA',
        ]);
    }

    // June 14, 2026 is a Sunday
    $fechaFinde = '2026-06-14';

    Livewire::actingAs($admin)
        ->test('pages::asistencias.index')
        ->set('asignacionId', $asignacion->id)
        ->set('fecha', $fechaFinde)
        ->assertSet('errorFecha', 'No se puede registrar asistencia los fines de semana.')
        ->call('guardar')
        ->assertDispatched('notify', message: 'No se puede registrar asistencia los fines de semana.', type: 'error');

    expect(Asistencia::where('materia_id', $materia->id)->where('fecha', $fechaFinde)->count())->toBe(0);
});

test('no puede guardar asistencia en dias sin horario programado', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();
    $seccion = Seccion::where('grado_id', $grado->id)->first() ?: Seccion::create(['grado_id' => $grado->id, 'ano_lectivo_id' => $ano->id, 'letra' => 'X', 'turno' => 'Mañana']);
    $materia = Materia::create(['nombre' => 'Materia Dia Sin Clase', 'codigo' => 'MAT_DSC_'.uniqid(), 'grado_id' => $grado->id]);
    $docente = Personal::create([
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'dui' => '12345678-'.rand(0, 9),
        'fecha_nacimiento' => '1980-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);
    $asignacion = AsignacionDocente::create([
        'ano_lectivo_id' => $ano->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'personal_id' => $docente->id,
    ]);

    // Create schedule for Thursday (4) only
    \App\Models\HorarioClase::create([
        'asignacion_docente_id' => $asignacion->id,
        'dia_semana' => 4, // Thursday
        'hora_inicio' => '07:00:00',
        'hora_fin' => '08:30:00',
    ]);

    // Ensure there is at least one student
    $matricula = Matricula::where('seccion_id', $seccion->id)->first();
    if (! $matricula) {
        $estudiante = Estudiante::create([
            'nie' => '1234569',
            'nombres' => 'Luis',
            'apellidos' => 'López',
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
    }

    // June 12, 2026 is a Friday (no class scheduled on Friday)
    $fechaViernes = '2026-06-12';

    Livewire::actingAs($admin)
        ->test('pages::asistencias.index')
        ->set('asignacionId', $asignacion->id)
        ->set('fecha', $fechaViernes)
        ->assertSet('errorFecha', 'Esta asignatura no tiene clases programadas para el día Viernes.')
        ->call('guardar')
        ->assertDispatched('notify', message: 'No hay clases programadas para esta asignatura en el día seleccionado.', type: 'error');

    expect(Asistencia::where('materia_id', $materia->id)->where('fecha', $fechaViernes)->count())->toBe(0);
});

