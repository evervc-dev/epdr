<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use Illuminate\Database\Seeder;

class AnoLectivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AnoLectivo::firstOrCreate([
            'anio' => now()->year,
        ], [
            'activo' => true,
            'fecha_inicio' => now()->year.'-01-15',
            'fecha_fin' => now()->year.'-10-31',
        ]);
    }
}
