<?php
// database/seeders/HorariosSeeder.php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\HorarioClase;
use App\Models\Seccion;
use Illuminate\Database\Seeder;

class HorariosSeeder extends Seeder
{
    /**
     * Bloques horarios disponibles en turno mañana.
     * Formato: [hora_inicio, hora_fin]
     */
    private array $bloques = [
        1 => ['07:00:00', '08:00:00'],
        2 => ['08:00:00', '09:00:00'],
        3 => ['09:00:00', '10:00:00'],
        4 => ['10:00:00', '11:00:00'],
        5 => ['11:00:00', '12:00:00'],
    ];

    /**
     * Días de la semana disponibles.
     */
    private array $dias = [1, 2, 3, 4, 5]; // Lun–Vie

    /**
     * Cuántas veces por semana se imparte cada materia.
     * Basado en el currículo de educación básica de El Salvador.
     */
    private array $frecuencias = [
        'Lenguaje'                  => 5,
        'Matemática'                => 5,
        'Ciencias Naturales'        => 3,
        'Estudios Sociales'         => 3,
        'Inglés'                    => 3,
        'Informática'               => 2,
        'Educación Artística'       => 2,
        'Educación Física'          => 2,
        'Moral, Urbanidad y Cívica' => 2,
    ];

    /**
     * Crea un bloque de horario si el slot está libre para la sección y el docente.
     */
    private function crearBloque(
        int $asignacionId,
        int $dia,
        int $bloqueNum,
        array &$ocupadoSeccion,  // [dia][bloque] => bool
        array &$ocupadoDocente   // [personalId][dia][bloque] => bool
    ): bool {
        if (! isset($this->bloques[$bloqueNum])) {
            return false;
        }

        // Slot ocupado en esta sección
        if (! empty($ocupadoSeccion[$dia][$bloqueNum])) {
            return false;
        }

        // Slot ocupado para este docente (en cualquier sección)
        $asignacion = AsignacionDocente::find($asignacionId);
        $personalId = $asignacion->personal_id;

        if (! empty($ocupadoDocente[$personalId][$dia][$bloqueNum])) {
            return false;
        }

        [$inicio, $fin] = $this->bloques[$bloqueNum];

        HorarioClase::create([
            'asignacion_docente_id' => $asignacionId,
            'dia_semana'            => $dia,
            'hora_inicio'           => $inicio,
            'hora_fin'              => $fin,
        ]);

        $ocupadoSeccion[$dia][$bloqueNum]              = true;
        $ocupadoDocente[$personalId][$dia][$bloqueNum] = true;

        return true;
    }

    public function run(): void
    {
        $ano = AnoLectivo::where('activo', true)->first();

        if (! $ano) {
            $this->command->error('No hay un año lectivo activo.');
            return;
        }

        // Elimina horarios previos del año activo para evitar duplicados
        HorarioClase::whereHas('asignacionDocente', fn($q) => $q->where('ano_lectivo_id', $ano->id))->delete();

        // Rastrea qué slots ya están ocupados por docente (global entre secciones)
        $ocupadoDocente = [];

        $secciones = Seccion::where('ano_lectivo_id', $ano->id)
            ->with('grado')
            ->get();

        foreach ($secciones as $seccion) {
            $this->command->line("→ Generando horario: {$seccion->grado->nombre} Sección {$seccion->letra}...");

            // Asignaciones de esta sección, ordenadas por frecuencia descendente
            // (Lenguaje y Matemática primero, así toman los mejores slots)
            $asignaciones = AsignacionDocente::where('seccion_id', $seccion->id)
                ->where('ano_lectivo_id', $ano->id)
                ->with(['materia', 'personal'])
                ->get()
                ->sortByDesc(fn($a) => $this->frecuencias[$a->materia->nombre] ?? 2);

            // Slots ocupados en esta sección [dia][bloque]
            $ocupadoSeccion = [];

            // Lista de slots disponibles en orden natural, se rota para distribuir bien
            // Generamos todos los slots posibles: dia x bloque
            $slots = [];
            foreach ($this->dias as $dia) {
                foreach (array_keys($this->bloques) as $bloque) {
                    $slots[] = [$dia, $bloque];
                }
            }
            // 25 slots en total (5 días x 5 bloques)

            foreach ($asignaciones as $asignacion) {
                $nombreMateria = $asignacion->materia->nombre;
                $frecuencia    = $this->frecuencias[$nombreMateria] ?? 2;
                $asignados     = 0;

                // Intentamos distribuir la frecuencia lo más uniforme posible en la semana.
                // Para eso rotamos los slots en función del índice de la materia.
                $slotsOrdenados = $slots;

                // Evitar que todas las materias con misma frecuencia caigan en los mismos días:
                // mezclamos los slots de forma determinista por nombre de materia
                $seed = crc32($nombreMateria . $seccion->id);
                usort($slotsOrdenados, function ($a, $b) use ($seed) {
                    $hashA = crc32($seed . $a[0] . $a[1]);
                    $hashB = crc32($seed . $b[0] . $b[1]);
                    return $hashA <=> $hashB;
                });

                foreach ($slotsOrdenados as [$dia, $bloque]) {
                    if ($asignados >= $frecuencia) {
                        break;
                    }

                    $creado = $this->crearBloque(
                        $asignacion->id,
                        $dia,
                        $bloque,
                        $ocupadoSeccion,
                        $ocupadoDocente
                    );

                    if ($creado) {
                        $asignados++;
                    }
                }

                if ($asignados < $frecuencia) {
                    $this->command->warn(
                        "  ⚠  {$nombreMateria} en {$seccion->grado->nombre} {$seccion->letra}: "
                        . "solo se asignaron {$asignados}/{$frecuencia} bloques (slots insuficientes o docente ocupado)."
                    );
                }
            }
        }

        $this->command->info('✅ Horarios generados correctamente.');
    }
}
