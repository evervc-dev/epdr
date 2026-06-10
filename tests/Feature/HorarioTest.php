<?php

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\Grado;
use App\Models\HorarioClase;
use App\Models\Materia;
use App\Models\Personal;
use App\Models\Seccion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin y director pueden ver la pagina de horarios de clase', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/horarios');
    $response->assertStatus(200);

    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/horarios');
    $response->assertStatus(200);
});

test('docente puede ver la pagina de horarios de clase', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/horarios');
    $response->assertStatus(200);
});

test('puede crear y eliminar bloques de horario sin colisiones', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();
    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();

    $seccion = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'W',
        'turno' => 'Mañana',
    ]);

    $materia = Materia::create([
        'nombre' => 'Materia Test',
        'codigo' => 'MT01',
        'grado_id' => $grado->id,
    ]);

    $persona = Personal::create([
        'dui' => '99999999-9',
        'nombres' => 'Profesor',
        'apellidos' => 'Test',
        'fecha_nacimiento' => '1985-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);

    $asignacion = AsignacionDocente::create([
        'personal_id' => $persona->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    // Test Livewire component scheduling a class slot
    Livewire::actingAs($admin)
        ->test('pages::horarios.index')
        ->set('seccionId', $seccion->id)
        ->set('asignacionId', $asignacion->id)
        ->set('diaSemana', 1) // Lunes
        ->set('horaInicio', '07:00')
        ->set('horaFin', '08:40')
        ->call('programar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('horarios_clases', [
        'asignacion_docente_id' => $asignacion->id,
        'dia_semana' => 1,
        'hora_inicio' => '07:00:00',
        'hora_fin' => '08:40:00',
    ]);

    $horario = HorarioClase::where('asignacion_docente_id', $asignacion->id)->first();

    // Test removing class slot
    Livewire::actingAs($admin)
        ->test('pages::horarios.index')
        ->set('seccionId', $seccion->id)
        ->call('eliminarBlock', $horario->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('horarios_clases', [
        'id' => $horario->id,
    ]);
});

test('evita colision de docente en mismo horario', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();
    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();

    // Two sections
    $seccion1 = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'X',
        'turno' => 'Mañana',
    ]);
    $seccion2 = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'Y',
        'turno' => 'Mañana',
    ]);

    $materia = Materia::create([
        'nombre' => 'Materia Test',
        'codigo' => 'MT02',
        'grado_id' => $grado->id,
    ]);

    // Same teacher for both
    $persona = Personal::create([
        'dui' => '99999999-8',
        'nombres' => 'Profesor',
        'apellidos' => 'Compartido',
        'fecha_nacimiento' => '1985-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);

    $asignacion1 = AsignacionDocente::create([
        'personal_id' => $persona->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion1->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    $asignacion2 = AsignacionDocente::create([
        'personal_id' => $persona->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion2->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    // First scheduled class: Lunes 07:00 - 08:40 in seccion 1
    HorarioClase::create([
        'asignacion_docente_id' => $asignacion1->id,
        'dia_semana' => 1,
        'hora_inicio' => '07:00:00',
        'hora_fin' => '08:40:00',
    ]);

    // Attempt second class for same teacher overlapping: Lunes 08:00 - 09:30 in seccion 2
    Livewire::actingAs($admin)
        ->test('pages::horarios.index')
        ->set('seccionId', $seccion2->id)
        ->set('asignacionId', $asignacion2->id)
        ->set('diaSemana', 1)
        ->set('horaInicio', '08:00')
        ->set('horaFin', '09:30')
        ->call('programar')
        ->assertHasErrors(['general']);

    $this->assertDatabaseMissing('horarios_clases', [
        'asignacion_docente_id' => $asignacion2->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00:00',
    ]);
});

test('evita colision de seccion en mismo horario', function () {
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
        'nombre' => 'Matemática',
        'codigo' => 'MAT_COLL1',
        'grado_id' => $grado->id,
    ]);

    $materia2 = Materia::create([
        'nombre' => 'Lenguaje',
        'codigo' => 'LEN_COLL1',
        'grado_id' => $grado->id,
    ]);

    // Two different teachers
    $persona1 = Personal::create([
        'dui' => '99999999-6',
        'nombres' => 'Profesor',
        'apellidos' => 'Uno',
        'fecha_nacimiento' => '1985-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);

    $persona2 = Personal::create([
        'dui' => '99999999-5',
        'nombres' => 'Profesor',
        'apellidos' => 'Dos',
        'fecha_nacimiento' => '1985-01-01',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2020-01-01',
        'activo' => true,
    ]);

    $asignacion1 = AsignacionDocente::create([
        'personal_id' => $persona1->id,
        'materia_id' => $materia1->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    $asignacion2 = AsignacionDocente::create([
        'personal_id' => $persona2->id,
        'materia_id' => $materia2->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    // First scheduled class: Lunes 07:00 - 08:40 in seccion (Matemática)
    HorarioClase::create([
        'asignacion_docente_id' => $asignacion1->id,
        'dia_semana' => 1,
        'hora_inicio' => '07:00:00',
        'hora_fin' => '08:40:00',
    ]);

    // Attempt second class for same section overlapping: Lunes 08:30 - 10:00 (Lenguaje)
    Livewire::actingAs($admin)
        ->test('pages::horarios.index')
        ->set('seccionId', $seccion->id)
        ->set('asignacionId', $asignacion2->id)
        ->set('diaSemana', 1)
        ->set('horaInicio', '08:30')
        ->set('horaFin', '10:00')
        ->call('programar')
        ->assertHasErrors(['general']);

    $this->assertDatabaseMissing('horarios_clases', [
        'asignacion_docente_id' => $asignacion2->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:30:00',
    ]);
});
