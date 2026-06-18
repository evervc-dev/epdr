<?php

use App\Models\AnoLectivo;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('no puede reactivar matricula si el estudiante ya tiene una matricula activa en otra seccion', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();
    $ano = AnoLectivo::where('activo', true)->first();
    $grado = Grado::first();

    // Create Section A and Section B
    $seccionA = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'A',
        'turno' => 'Mañana',
    ]);

    $seccionB = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'B',
        'turno' => 'Mañana',
    ]);

    // Create Student
    $estudiante = Estudiante::create([
        'nie' => '99887766',
        'nombres' => 'Carlos',
        'apellidos' => 'Martínez',
        'fecha_nacimiento' => '2015-05-05',
        'genero' => 'M',
    ]);

    // Create old enrollment in Section B (now Trasladada)
    $matriculaB = Matricula::create([
        'estudiante_id' => $estudiante->id,
        'seccion_id' => $seccionB->id,
        'ano_lectivo_id' => $ano->id,
        'tipo_inscripcion' => 'N',
        'fecha_matricula' => '2026-06-01',
        'estado' => 'TRASLADADA',
    ]);

    // Create active enrollment in Section A
    $matriculaA = Matricula::create([
        'estudiante_id' => $estudiante->id,
        'seccion_id' => $seccionA->id,
        'ano_lectivo_id' => $ano->id,
        'tipo_inscripcion' => 'T',
        'fecha_matricula' => '2026-06-02',
        'estado' => 'ACTIVA',
    ]);

    // Try to reactivate the enrollment in Section B
    Livewire::actingAs($admin)
        ->test('pages::estudiantes.matriculas')
        ->set('anioLectivoId', $ano->id)
        ->set('seccionId', $seccionB->id)
        ->call('cambiarEstado', $matriculaB->id, 'ACTIVA')
        ->assertDispatched('notify', message: "El estudiante ya tiene una matrícula ACTIVA en la sección {$seccionA->nombre_completo}.", type: 'error');

    // Verify database values did not change
    expect($matriculaB->fresh()->estado)->toBe('TRASLADADA');
    expect($matriculaA->fresh()->estado)->toBe('ACTIVA');
});
