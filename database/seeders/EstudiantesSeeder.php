<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class EstudiantesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anoLectivo = AnoLectivo::where('activo', true)->first();

        if (!$anoLectivo) {
            $this->command->warn('No hay un año lectivo activo. No se pueden matricular estudiantes.');
            return;
        }

        $namesFilePath = base_path('names-data.json');
        if (!File::exists($namesFilePath)) {
            $this->command->error('El archivo names-data.json no existe en la raíz del proyecto.');
            return;
        }

        $namesData = json_decode(File::get($namesFilePath), true);
        $lastnames = $namesData['lastname'];
        $femalenames = $namesData['femalename'];
        $malenames = $namesData['malename'];

        // Obtener todas las secciones con sus grados
        $secciones = Seccion::with('grado')->where('ano_lectivo_id', $anoLectivo->id)->get();

        $this->command->info("Poblando estudiantes para {$secciones->count()} secciones...");

        $nieCounter = 20260001;

        foreach ($secciones as $seccion) {
            $grado = $seccion->grado;
            // Calcular edad según el orden del grado (edad = orden + 3)
            $edad = $grado->orden + 3;
            $birthYear = $anoLectivo->anio - $edad;

            // Crear 10 estudiantes por sección (5 niños y 5 niñas)
            for ($i = 0; $i < 10; $i++) {
                $genero = ($i % 2 === 0) ? 'M' : 'F';
                
                // Generar nombres
                if ($genero === 'M') {
                    $nombre1 = $malenames[array_rand($malenames)];
                    $nombre2 = $malenames[array_rand($malenames)];
                    while ($nombre1 === $nombre2) {
                        $nombre2 = $malenames[array_rand($malenames)];
                    }
                } else {
                    $nombre1 = $femalenames[array_rand($femalenames)];
                    $nombre2 = $femalenames[array_rand($femalenames)];
                    while ($nombre1 === $nombre2) {
                        $nombre2 = $femalenames[array_rand($femalenames)];
                    }
                }
                $nombres = "{$nombre1} {$nombre2}";

                // Generar apellidos
                $apellido1 = $lastnames[array_rand($lastnames)];
                $apellido2 = $lastnames[array_rand($lastnames)];
                while ($apellido1 === $apellido2) {
                    $apellido2 = $lastnames[array_rand($lastnames)];
                }
                $apellidos = "{$apellido1} {$apellido2}";

                // Fecha de nacimiento aleatoria en el año
                $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
                $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
                $fechaNacimiento = "{$birthYear}-{$month}-{$day}";

                // Probabilidades para otros atributos
                $esRepitente = (rand(1, 100) > 95);
                $perteneceDai = (rand(1, 100) > 98);

                $actividadEconomicaOptions = [
                    'No trabaja', 'Caña de azúcar', 'Pesca', 'Pepenador',
                    'Trabajo doméstico', 'Cohetería', 'Café', 'Trabajos ambulantes',
                    'Limpia autos/botas', 'Trabajos agrícolas', 'Otros'
                ];
                $actVal = rand(1, 100);
                $actividadEconomica = ($actVal <= 90) ? 'No trabaja' : $actividadEconomicaOptions[array_rand($actividadEconomicaOptions)];

                $convivenciaOptions = [
                    'Vive con ambos', 'Vive con la madre', 'Vive con el padre', 
                    'Vive con familiares', 'No vive con familiares'
                ];
                $convVal = rand(1, 100);
                if ($convVal <= 70) {
                    $convivencia = 'Vive con ambos';
                } elseif ($convVal <= 85) {
                    $convivencia = 'Vive con la madre';
                } elseif ($convVal <= 93) {
                    $convivencia = 'Vive con el padre';
                } elseif ($convVal <= 98) {
                    $convivencia = 'Vive con familiares';
                } else {
                    $convivencia = 'No vive con familiares';
                }

                // Generar NIE único
                $nie = (string)$nieCounter;
                while (Estudiante::where('nie', $nie)->exists()) {
                    $nieCounter++;
                    $nie = (string)$nieCounter;
                }
                $nieCounter++;

                // Crear Estudiante
                $estudiante = Estudiante::create([
                    'nie' => $nie,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'genero' => $genero,
                    'es_repitente' => $esRepitente,
                    'tiene_extraedad' => false,
                    'pertenece_dai' => $perteneceDai,
                    'actividad_economica' => $actividadEconomica,
                    'convivencia' => $convivencia,
                ]);

                // Registrar Matrícula
                $tipoInscripcionOptions = ['V', 'N', 'T'];
                $tipoVal = rand(1, 100);
                if ($tipoVal <= 80) {
                    $tipoInscripcion = 'V';
                } elseif ($tipoVal <= 95) {
                    $tipoInscripcion = 'N';
                } else {
                    $tipoInscripcion = 'T';
                }

                $fechaMatricula = Carbon::parse($anoLectivo->fecha_inicio)->addDays(rand(0, 10))->format('Y-m-d');

                Matricula::create([
                    'estudiante_id' => $estudiante->id,
                    'seccion_id' => $seccion->id,
                    'ano_lectivo_id' => $anoLectivo->id,
                    'tipo_inscripcion' => $tipoInscripcion,
                    'fecha_matricula' => $fechaMatricula,
                    'estado' => 'ACTIVA',
                ]);
            }
        }

        $this->command->info("Estudiantes y matrículas creados correctamente. Total: " . (Seccion::count() * 10) . " estudiantes matriculados.");
    }
}
