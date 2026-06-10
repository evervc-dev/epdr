<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use App\Models\Grado;
use App\Models\Seccion;
use Illuminate\Database\Seeder;

class GradosSeccionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anoLectivo = AnoLectivo::where('activo', true)->first();

        if (! $anoLectivo) {
            return;
        }

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
            $grado = Grado::firstOrCreate(
                ['nombre' => $gradoData['nombre']],
                ['nivel' => $gradoData['nivel'], 'orden' => $gradoData['orden']]
            );

            foreach (['A', 'B', 'C', 'D'] as $letra) {
                Seccion::firstOrCreate([
                    'grado_id' => $grado->id,
                    'ano_lectivo_id' => $anoLectivo->id,
                    'letra' => $letra,
                ], [
                    'turno' => 'Mañana',
                ]);
            }
        }
    }
}
