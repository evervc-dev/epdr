<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AnoLectivoSeeder::class,
            GradosSeccionesSeeder::class,
            MateriasSeeder::class,
            AdminUserSeeder::class,
            EstudiantesSeeder::class,
            PersonalDocenteSeeder::class,
            AsignacionesDocenteSeeder::class,
            HorariosSeeder::class,
            ProductosSeeder::class,
        ]);
    }
}
