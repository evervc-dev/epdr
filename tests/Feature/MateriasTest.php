<?php

use App\Models\Grado;
use App\Models\Materia;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin puede acceder a la pagina de gestion de materias', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/admin/materias');
    $response->assertStatus(200);
});

test('docente no puede acceder a la pagina de gestion de materias', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/admin/materias');
    $response->assertStatus(403);
});

test('puede crear, editar y eliminar una materia', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();
    $grado = Grado::first();

    Livewire::actingAs($admin)
        ->test('pages::admin.materias')
        ->set('nombre', 'Materia Test')
        ->set('codigo', 'TEST01')
        ->set('gradoId', $grado->id)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSet('nombre', '')
        ->assertSet('codigo', '')
        ->assertSet('gradoId', null);

    $this->assertDatabaseHas('materias', [
        'nombre' => 'Materia Test',
        'codigo' => 'TEST01',
        'grado_id' => $grado->id,
    ]);

    $materia = Materia::where('codigo', 'TEST01')->first();

    Livewire::actingAs($admin)
        ->test('pages::admin.materias')
        ->call('editar', $materia->id)
        ->assertSet('nombre', 'Materia Test')
        ->assertSet('codigo', 'TEST01')
        ->assertSet('gradoId', $grado->id)
        ->set('nombre', 'Materia Editada')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('materias', [
        'id' => $materia->id,
        'nombre' => 'Materia Editada',
        'codigo' => 'TEST01',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.materias')
        ->call('eliminar', $materia->id);

    $this->assertDatabaseMissing('materias', [
        'id' => $materia->id,
    ]);
});

test('puede buscar una materia por nombre o codigo', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();
    $grado = Grado::first();

    $materia1 = Materia::create([
        'nombre' => 'Matemáticas Especiales',
        'codigo' => 'MATESP',
        'grado_id' => $grado->id,
    ]);

    $materia2 = Materia::create([
        'nombre' => 'Lenguaje Avanzado',
        'codigo' => 'LENGAV',
        'grado_id' => $grado->id,
    ]);

    // Buscar por nombre
    Livewire::actingAs($admin)
        ->test('pages::admin.materias')
        ->set('search', 'Matemáticas')
        ->assertViewHas('materias', function ($materias) use ($materia1, $materia2) {
            return $materias->contains($materia1) && !$materias->contains($materia2);
        });

    // Buscar por código
    Livewire::actingAs($admin)
        ->test('pages::admin.materias')
        ->set('search', 'LENGAV')
        ->assertViewHas('materias', function ($materias) use ($materia1, $materia2) {
            return !$materias->contains($materia1) && $materias->contains($materia2);
        });
});

