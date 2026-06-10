<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // All system permissions
        $permissions = [
            // Admin Module
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'roles.gestionar', 'anio_lectivo.gestionar', 'grados.gestionar',
            'secciones.gestionar', 'materias.gestionar',

            // Student Module
            'estudiantes.ver', 'estudiantes.crear', 'estudiantes.editar', 'estudiantes.eliminar',
            'matriculas.gestionar', 'tutores.gestionar', 'reportes.caracterizacion',

            // Grades Module
            'notas.ver_todas', 'notas.ver_propias', 'notas.registrar', 'notas.editar',
            'asignaciones.gestionar',

            // Attendance Module
            'asistencias.ver_todas', 'asistencias.ver_propias', 'asistencias.registrar',
            'reportes.asistencias',

            // Inventory Module
            'inventario.ver', 'inventario.productos', 'inventario.lotes',
            'inventario.movimientos', 'inventario.auditorias', 'reportes.inventario',

            // Staff Module
            'personal.ver', 'personal.crear', 'personal.editar', 'personal.eliminar',

            // Reports Module
            'informes.rendimiento', 'reportes.estadisticos',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $director = Role::firstOrCreate(['name' => 'director']);
        $docente = Role::firstOrCreate(['name' => 'docente']);
        $bodega = Role::firstOrCreate(['name' => 'bodega']);

        // Sync permissions
        $admin->syncPermissions(Permission::all());

        $director->syncPermissions([
            'estudiantes.ver', 'estudiantes.crear', 'estudiantes.editar',
            'matriculas.gestionar', 'tutores.gestionar', 'reportes.caracterizacion',
            'notas.ver_todas', 'asignaciones.gestionar',
            'asistencias.ver_todas', 'reportes.asistencias',
            'inventario.ver', 'inventario.auditorias', 'reportes.inventario',
            'personal.ver', 'personal.crear', 'personal.editar',
            'informes.rendimiento', 'reportes.estadisticos',
        ]);

        $docente->syncPermissions([
            'estudiantes.ver',
            'notas.ver_propias', 'notas.registrar', 'notas.editar',
            'asistencias.ver_propias', 'asistencias.registrar',
        ]);

        $bodega->syncPermissions([
            'inventario.ver', 'inventario.productos', 'inventario.lotes',
            'inventario.movimientos', 'inventario.auditorias', 'reportes.inventario',
        ]);
    }
}
