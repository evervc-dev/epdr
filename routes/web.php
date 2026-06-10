<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::livewire('/login', 'pages::auth.login')->name('login')->middleware('guest');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Dashboard (Home)
    Route::livewire('/', 'pages::dashboard')->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/usuarios', 'pages::admin.usuarios')->name('usuarios');
    Route::livewire('/ano-lectivo', 'pages::admin.ano-lectivo')->name('ano-lectivo');
    Route::livewire('/grados-secciones', 'pages::admin.grados-secciones')->name('grados-secciones');
    Route::livewire('/materias', 'pages::admin.materias')->name('materias');
});

// Student Routes
Route::middleware(['auth', 'role:admin|director'])->prefix('estudiantes')->name('estudiantes.')->group(function () {
    Route::livewire('/', 'pages::estudiantes.index')->name('index');
    Route::livewire('/crear', 'pages::estudiantes.formulario')->name('crear');
    Route::livewire('/{id}/editar', 'pages::estudiantes.formulario')->name('editar');
    Route::livewire('/matriculas', 'pages::estudiantes.matriculas')->name('matriculas');
});

// Personal Routes
Route::middleware(['auth', 'role:admin|director'])->prefix('personal')->name('personal.')->group(function () {
    Route::livewire('/', 'pages::personal.index')->name('index');
    Route::livewire('/crear', 'pages::personal.formulario')->name('crear');
    Route::livewire('/{id}/editar', 'pages::personal.formulario')->name('editar');
    Route::livewire('/{id}/asignaciones', 'pages::personal.asignaciones')->name('asignaciones');
});

// Grades (Notas) Routes
Route::middleware(['auth', 'role:admin|director|docente'])->prefix('notas')->name('notas.')->group(function () {
    Route::livewire('/', 'pages::notas.index')->name('index');
    Route::livewire('/registro/{asignacion}/{trimestre}', 'pages::notas.registro')->name('registro');
});

// Attendance (Asistencias) Routes
Route::middleware(['auth', 'role:admin|director|docente'])->prefix('asistencias')->name('asistencias.')->group(function () {
    Route::livewire('/', 'pages::asistencias.index')->name('index');
});

// Schedules (Horarios) Routes
Route::middleware(['auth', 'role:admin|director|docente'])->prefix('horarios')->name('horarios.')->group(function () {
    Route::livewire('/', 'pages::horarios.index')->name('index');
});

Route::middleware(['auth', 'role:admin|director'])->group(function () {
    Route::livewire('/asistencias/reporte', 'pages::asistencias.reporte')->name('asistencias.reporte');
});

// Inventory (Inventario) Routes
Route::middleware(['auth', 'role:admin|bodega'])->prefix('inventario')->name('inventario.')->group(function () {
    Route::livewire('/productos', 'pages::inventario.productos')->name('productos');
    Route::livewire('/lotes', 'pages::inventario.lotes')->name('lotes');
    Route::livewire('/movimientos', 'pages::inventario.movimientos')->name('movimientos');
});

Route::middleware(['auth', 'role:admin|director|bodega'])->group(function () {
    Route::livewire('/inventario/auditoria', 'pages::inventario.auditoria')->name('inventario.auditoria');
});

// Reports (Reportes) Routes
Route::middleware(['auth'])->group(function () {
    Route::middleware('role:admin|director')->prefix('reportes')->name('reportes.')->group(function () {
        Route::livewire('/rendimiento', 'pages::reportes.rendimiento')->name('rendimiento');
        Route::livewire('/caracterizacion', 'pages::reportes.caracterizacion')->name('caracterizacion');
        Route::livewire('/estadisticos', 'pages::reportes.estadisticos')->name('estadisticos');
    });

    Route::middleware('role:admin|director|bodega')->prefix('reportes')->name('reportes.')->group(function () {
        Route::livewire('/inventario', 'pages::reportes.inventario')->name('inventario');
    });
});
