# Seeders y Datos Iniciales

---

## Orden de Ejecución en `DatabaseSeeder`

```php
public function run(): void
{
    $this->call([
        RolesPermisosSeeder::class,   // 1. Roles y permisos (Spatie)
        AnoLectivoSeeder::class,       // 2. Año lectivo activo
        GradosSeccionesSeeder::class,  // 3. Grados + secciones iniciales
        MateriasSeeder::class,         // 4. Materias por grado
        AdminUserSeeder::class,        // 5. Usuario administrador inicial
    ]);
}
```

---

## `RolesPermisosSeeder`

Crear los 4 roles y todos los permisos definidos en `02_roles_permisos.md`.  
Asignar permisos a roles según la tabla de esa especificación.

```php
// Fragmento de referencia
$admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
$director = Role::firstOrCreate(['name' => 'director', 'guard_name' => 'web']);
$docente = Role::firstOrCreate(['name' => 'docente', 'guard_name' => 'web']);
$bodega = Role::firstOrCreate(['name' => 'bodega', 'guard_name' => 'web']);

// Admin recibe todos los permisos
$admin->syncPermissions(Permission::all());

// Director: todos excepto gestión de usuarios/roles/configuración técnica
$director->syncPermissions([
    'estudiantes.ver', 'estudiantes.crear', 'estudiantes.editar',
    'matriculas.gestionar', 'tutores.gestionar', 'reportes.caracterizacion',
    'notas.ver_todas', 'asignaciones.gestionar',
    'asistencias.ver_todas', 'reportes.asistencias',
    'inventario.ver', 'inventario.auditorias', 'reportes.inventario',
    'personal.ver', 'personal.crear', 'personal.editar',
    'informes.rendimiento', 'reportes.estadisticos',
]);

// Docente: solo lo propio
$docente->syncPermissions([
    'estudiantes.ver',
    'notas.ver_propias', 'notas.registrar', 'notas.editar',
    'asistencias.ver_propias', 'asistencias.registrar',
]);

// Bodega: solo inventario
$bodega->syncPermissions([
    'inventario.ver', 'inventario.productos', 'inventario.lotes',
    'inventario.movimientos', 'inventario.auditorias', 'reportes.inventario',
]);
```

---

## `AnoLectivoSeeder`

```php
AnoLectivo::create([
    'anio'         => now()->year,
    'activo'       => true,
    'fecha_inicio' => now()->year . '-01-15',
    'fecha_fin'    => now()->year . '-10-31',
]);
```

---

## `GradosSeccionesSeeder`

```php
$anoLectivo = AnoLectivo::where('activo', true)->first();

$grados = [
    // Parvularia
    ['nombre' => 'Parvularia 4 años', 'nivel' => 'parvularia', 'orden' => 1],
    ['nombre' => 'Parvularia 5 años', 'nivel' => 'parvularia', 'orden' => 2],
    ['nombre' => 'Parvularia 6 años', 'nivel' => 'parvularia', 'orden' => 3],
    // Básica
    ['nombre' => '1° Grado',  'nivel' => 'basica', 'orden' => 4],
    ['nombre' => '2° Grado',  'nivel' => 'basica', 'orden' => 5],
    ['nombre' => '3° Grado',  'nivel' => 'basica', 'orden' => 6],
    ['nombre' => '4° Grado',  'nivel' => 'basica', 'orden' => 7],
    ['nombre' => '5° Grado',  'nivel' => 'basica', 'orden' => 8],
    ['nombre' => '6° Grado',  'nivel' => 'basica', 'orden' => 9],
    ['nombre' => '7° Grado',  'nivel' => 'basica', 'orden' => 10],
    ['nombre' => '8° Grado',  'nivel' => 'basica', 'orden' => 11],
    ['nombre' => '9° Grado',  'nivel' => 'basica', 'orden' => 12],
];

foreach ($grados as $gradoData) {
    $grado = Grado::create($gradoData);
    foreach (['A', 'B', 'C', 'D'] as $letra) {
        Seccion::create([
            'grado_id'       => $grado->id,
            'ano_lectivo_id' => $anoLectivo->id,
            'letra'          => $letra,
            'turno'          => 'Mañana',
        ]);
    }
}
```

---

## `MateriasSeeder`

Crear las materias del currículo nacional del MINED para educación básica de El Salvador.  
Ejemplo orientativo (ajustar al currículo oficial):

```php
$materiasPorGrado = [
    // Grados 1–3
    1 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
    2 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
    3 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
    // Grados 4–6
    4 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
    // ... continuar para cada grado
    // Grados 7–9 agregar: Inglés, Informática
];

foreach ($materiasPorGrado as $gradoOrden => $materias) {
    $grado = Grado::where('orden', $gradoOrden + 3)->first(); // +3 por parvularia
    foreach ($materias as $i => $materia) {
        Materia::create([
            'nombre'   => $materia,
            'codigo'   => strtoupper(substr($materia, 0, 3)) . str_pad($gradoOrden, 2, '0', STR_PAD_LEFT),
            'grado_id' => $grado->id,
        ]);
    }
}
```

---

## `AdminUserSeeder`

```php
$user = User::create([
    'name'     => 'Administrador',
    'email'    => 'admin@cepja.edu.sv',
    'password' => Hash::make('Admin@2025!'), // Cambiar en producción
]);

$user->assignRole('admin');
```

> **Importante:** Cambiar la contraseña del administrador en el primer inicio de sesión en producción.
