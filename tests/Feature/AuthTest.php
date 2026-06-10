<?php

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Personal;
use App\Models\Seccion;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Seed database before each test to setup roles and initial admin user
    $this->seed();
});

test('invitado puede ver la pagina de login', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('usuario no autenticado es redireccionado al login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('administrador autenticado puede ver el dashboard', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/');
    $response->assertStatus(200);
});

test('administrador puede ver la pagina de gestion de usuarios', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/admin/usuarios');
    $response->assertStatus(200);
});

test('usuario no administrador no puede ver la pagina de gestion de usuarios', function () {
    // Create a new user and assign a director role
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/admin/usuarios');
    $response->assertStatus(403);
});

test('administrador puede ver la pagina de gestion de ano lectivo', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/admin/ano-lectivo');
    $response->assertStatus(200);
});

test('usuario no administrador no puede ver la pagina de gestion de ano lectivo', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/admin/ano-lectivo');
    $response->assertStatus(403);
});

test('administrador puede ver la pagina de gestion de grados y secciones', function () {
    $admin = User::where('email', 'admin@cepja.edu.sv')->first();

    $response = $this->actingAs($admin)->get('/admin/grados-secciones');
    $response->assertStatus(200);
});

test('usuario no administrador no puede ver la pagina de gestion de grados y secciones', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/admin/grados-secciones');
    $response->assertStatus(403);
});

test('director o administrador puede ver el listado de estudiantes', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/estudiantes');
    $response->assertStatus(200);
});

test('docente no puede ver el listado de estudiantes', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/estudiantes');
    $response->assertStatus(403);
});

test('director o administrador puede ver el formulario de creacion de estudiante', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/estudiantes/crear');
    $response->assertStatus(200);
});

test('docente no puede ver el formulario de creacion de estudiante', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/estudiantes/crear');
    $response->assertStatus(403);
});

test('director o administrador puede ver la pagina de gestion de matriculas', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/estudiantes/matriculas');
    $response->assertStatus(200);
});

test('docente no puede ver la pagina de gestion de matriculas', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/estudiantes/matriculas');
    $response->assertStatus(403);
});

test('director o administrador puede ver el listado de personal', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/personal');
    $response->assertStatus(200);
});

test('docente no puede ver el listado de personal', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/personal');
    $response->assertStatus(403);
});

test('director o administrador puede ver el formulario de creacion de personal', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/personal/crear');
    $response->assertStatus(200);
});

test('docente no puede ver el formulario de creacion de personal', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/personal/crear');
    $response->assertStatus(403);
});

test('director o administrador puede ver la pagina de asignaciones de docente', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $persona = Personal::create([
        'dui' => '00000000-0',
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'fecha_nacimiento' => '1980-05-15',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2010-01-15',
        'activo' => true,
    ]);

    $response = $this->actingAs($director)->get("/personal/{$persona->id}/asignaciones");
    $response->assertStatus(200);
});

test('docente no puede ver la pagina de asignaciones de docente', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $persona = Personal::create([
        'dui' => '00000000-0',
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'fecha_nacimiento' => '1980-05-15',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2010-01-15',
        'activo' => true,
    ]);

    $response = $this->actingAs($docente)->get("/personal/{$persona->id}/asignaciones");
    $response->assertStatus(403);
});

test('usuario autenticado con rol docente, director o admin puede ver la pagina de seleccion de notas', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/notas');
    $response->assertStatus(200);
});

test('docente puede ver la pagina de registro de notas de su propia asignacion', function () {
    $ano = AnoLectivo::create([
        'anio' => 2027,
        'activo' => true,
        'fecha_inicio' => '2027-01-15',
        'fecha_fin' => '2027-10-31',
    ]);

    $grado = Grado::create([
        'nombre' => '1° Grado',
        'nivel' => 'basica',
        'orden' => 4,
    ]);

    $seccion = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'A',
        'turno' => 'Mañana',
    ]);

    $materia = Materia::create([
        'nombre' => 'Matemática',
        'codigo' => 'MAT99',
        'grado_id' => $grado->id,
    ]);

    $docenteUser = User::factory()->create();
    $docenteUser->assignRole('docente');

    $persona = Personal::create([
        'dui' => '00000000-1',
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'fecha_nacimiento' => '1980-05-15',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2010-01-15',
        'activo' => true,
        'user_id' => $docenteUser->id,
    ]);

    $asignacion = AsignacionDocente::create([
        'personal_id' => $persona->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    $response = $this->actingAs($docenteUser)->get("/notas/registro/{$asignacion->id}/1");
    $response->assertStatus(200);
});

test('docente no puede ver la pagina de registro de notas de otra asignacion', function () {
    $ano = AnoLectivo::create([
        'anio' => 2028,
        'activo' => true,
        'fecha_inicio' => '2028-01-15',
        'fecha_fin' => '2028-10-31',
    ]);

    $grado = Grado::create([
        'nombre' => '1° Grado',
        'nivel' => 'basica',
        'orden' => 4,
    ]);

    $seccion = Seccion::create([
        'grado_id' => $grado->id,
        'ano_lectivo_id' => $ano->id,
        'letra' => 'A',
        'turno' => 'Mañana',
    ]);

    $materia = Materia::create([
        'nombre' => 'Matemática',
        'codigo' => 'MAT98',
        'grado_id' => $grado->id,
    ]);

    $docenteUser1 = User::factory()->create();
    $docenteUser1->assignRole('docente');

    $persona1 = Personal::create([
        'dui' => '00000000-2',
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'fecha_nacimiento' => '1980-05-15',
        'genero' => 'M',
        'tipo' => 'docente',
        'fecha_ingreso' => '2010-01-15',
        'activo' => true,
        'user_id' => $docenteUser1->id,
    ]);

    $asignacion = AsignacionDocente::create([
        'personal_id' => $persona1->id,
        'materia_id' => $materia->id,
        'seccion_id' => $seccion->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    $docenteUser2 = User::factory()->create();
    $docenteUser2->assignRole('docente');

    $response = $this->actingAs($docenteUser2)->get("/notas/registro/{$asignacion->id}/1");
    $response->assertStatus(403);
});

test('admin, director y docente pueden acceder a control de asistencia diaria', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/asistencias');
    $response->assertStatus(200);

    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/asistencias');
    $response->assertStatus(200);
});

test('docente no puede acceder al reporte de asistencias', function () {
    $docente = User::factory()->create();
    $docente->assignRole('docente');

    $response = $this->actingAs($docente)->get('/asistencias/reporte');
    $response->assertStatus(403);
});

test('admin y director pueden acceder al reporte de asistencias', function () {
    $director = User::factory()->create();
    $director->assignRole('director');

    $response = $this->actingAs($director)->get('/asistencias/reporte');
    $response->assertStatus(200);
});
