<?php
// database/seeders/AsignacionesDocenteSeeder.php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\Materia;
use App\Models\Personal;
use App\Models\Seccion;
use Illuminate\Database\Seeder;

class AsignacionesDocenteSeeder extends Seeder
{
    private function materia(string $nombre, int $gradoOrden): ?Materia
    {
        return Materia::where('nombre', $nombre)
            ->whereHas('grado', fn($q) => $q->where('orden', $gradoOrden))
            ->first();
    }

    private function seccion(string $letra, int $gradoOrden, int $anoId): ?Seccion
    {
        return Seccion::where('letra', $letra)
            ->where('ano_lectivo_id', $anoId)
            ->whereHas('grado', fn($q) => $q->where('orden', $gradoOrden))
            ->first();
    }

    private function asignar(int $personalId, ?Materia $materia, ?Seccion $seccion, int $anoId): void
    {
        if (! $materia) {
            $this->command->warn('  ⚠  Materia no encontrada, se omite.');
            return;
        }
        if (! $seccion) {
            $this->command->warn('  ⚠  Sección no encontrada, se omite.');
            return;
        }
        if ($materia->grado_id !== $seccion->grado_id) {
            $this->command->warn('  ⚠  Materia y sección no pertenecen al mismo grado, se omite.');
            return;
        }

        $existe = AsignacionDocente::where('materia_id', $materia->id)
            ->where('seccion_id', $seccion->id)
            ->where('ano_lectivo_id', $anoId)
            ->exists();

        if ($existe) {
            $this->command->warn("  ⚠  {$materia->nombre} en sección {$seccion->letra} ya está asignada, se omite.");
            return;
        }

        AsignacionDocente::create([
            'personal_id'    => $personalId,
            'materia_id'     => $materia->id,
            'seccion_id'     => $seccion->id,
            'ano_lectivo_id' => $anoId,
        ]);
    }

    /**
     * Asigna la misma materia a un conjunto de grados y secciones.
     */
    private function asignarBloque(int $personalId, string $materia, array $grados, array $secciones, int $anoId): void
    {
        foreach ($grados as $grado) {
            foreach ($secciones as $letra) {
                $this->asignar(
                    $personalId,
                    $this->materia($materia, $grado),
                    $this->seccion($letra, $grado, $anoId),
                    $anoId
                );
            }
        }
    }

    /**
     * Órdenes de grado:
     *  1-3  = Parvularia (sin materias académicas)
     *  4-6  = 1°–3° Grado   (básica primer ciclo)
     *  7-9  = 4°–6° Grado   (básica segundo ciclo)
     * 10-12 = 7°–9° Grado   (básica tercer ciclo)
     *
     * Secciones por grado: A, B, C, D
     */
    public function run(): void
    {
        $ano = AnoLectivo::where('activo', true)->first();

        if (! $ano) {
            $this->command->error('No hay un año lectivo activo. Ejecuta primero AnoLectivoSeeder.');
            return;
        }

        $docentes = Personal::whereIn('dui', [
            // Originales
            '01234567-8', '02345678-9', '03456789-0', '04567890-1', '05678901-2',
            '06789012-3', '07890123-4', '08901234-5', '09012345-6', '10123456-7',
            '11234567-8', '12345678-9', '13456789-0', '14567890-1', '15678901-2',
            // Nuevos
            '20000001-1', '20000002-2', '20000003-3', '20000004-4',
            '20000005-5', '20000006-6', '20000007-7', '20000008-8',
            '20000009-9', '20000010-0', '20000011-1', '20000012-2',
            '20000013-3', '20000014-4', '20000015-5', '20000016-6',
            '20000017-7', '20000018-8',
            '20000019-9', '20000020-0',
            '20000021-1', '20000022-2',
            '20000023-3', '20000024-4',
            '20000025-5', '20000026-6',
        ])->pluck('id', 'dui');

        $id = fn(string $dui): ?int => $docentes[$dui] ?? null;

        // ================================================================
        // LENGUAJE Y LITERATURA
        // ================================================================

        // 1°–3° Grado  (órdenes 4–6): A/B → María Elena | C/D → Gloria Estela
        $this->command->line('→ Lenguaje 1°–3° Grado...');
        $this->asignarBloque($id('01234567-8'), 'Lenguaje', [4, 5, 6], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000001-1'), 'Lenguaje', [4, 5, 6], ['C', 'D'], $ano->id);

        // 4°–6° Grado  (órdenes 7–9): A/B → Lucía Sorto | C/D → Diana Guardado
        $this->command->line('→ Lenguaje 4°–6° Grado...');
        $this->asignarBloque($id('11234567-8'), 'Lenguaje', [7, 8, 9], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000004-4'), 'Lenguaje', [7, 8, 9], ['C', 'D'], $ano->id);

        // 7°–9° Grado (órdenes 10–12): A/B → Roberto Leiva | C/D → Silvia Aguilar
        $this->command->line('→ Lenguaje 7°–9° Grado...');
        $this->asignarBloque($id('20000002-2'), 'Lenguaje', [10, 11, 12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000003-3'), 'Lenguaje', [10, 11, 12], ['C', 'D'], $ano->id);

        // ================================================================
        // MATEMÁTICA
        // ================================================================

        // 1°–3° Grado: A/B → José Hernández | C/D → Julio Martínez
        $this->command->line('→ Matemática 1°–3° Grado...');
        $this->asignarBloque($id('02345678-9'), 'Matemática', [4, 5, 6], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000005-5'), 'Matemática', [4, 5, 6], ['C', 'D'], $ano->id);

        // 4°–6° Grado: A/B → Héctor Campos | C/D → Blanca Recinos
        $this->command->line('→ Matemática 4°–6° Grado...');
        $this->asignarBloque($id('10123456-7'), 'Matemática', [7, 8, 9], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000008-8'), 'Matemática', [7, 8, 9], ['C', 'D'], $ano->id);

        // 7°–9° Grado: A/B → Mirna Coreas | C/D → Fernando Ventura
        $this->command->line('→ Matemática 7°–9° Grado...');
        $this->asignarBloque($id('20000006-6'), 'Matemática', [10, 11, 12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000007-7'), 'Matemática', [10, 11, 12], ['C', 'D'], $ano->id);

        // ================================================================
        // CIENCIAS NATURALES
        // ================================================================

        // 1°–3° Grado: A/B → Ana Martínez | C/D → Álvaro Serrano
        $this->command->line('→ Ciencias Naturales 1°–3° Grado...');
        $this->asignarBloque($id('03456789-0'), 'Ciencias Naturales', [4, 5, 6], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000009-9'), 'Ciencias Naturales', [4, 5, 6], ['C', 'D'], $ano->id);

        // 4°–6° Grado: A/B → Edwin Rivas | C/D → Claudia Fuentes
        $this->command->line('→ Ciencias Naturales 4°–6° Grado...');
        $this->asignarBloque($id('12345678-9'), 'Ciencias Naturales', [7, 8, 9], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000012-2'), 'Ciencias Naturales', [7, 8, 9], ['C', 'D'], $ano->id);

        // 7°–9° Grado: A/B → Rebeca Iraheta | C/D → Nelson Ayala
        $this->command->line('→ Ciencias Naturales 7°–9° Grado...');
        $this->asignarBloque($id('20000010-0'), 'Ciencias Naturales', [10, 11, 12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000011-1'), 'Ciencias Naturales', [10, 11, 12], ['C', 'D'], $ano->id);

        // ================================================================
        // ESTUDIOS SOCIALES
        // ================================================================

        // 1°–3° Grado: A/B → Carlos Flores | C/D → Mauricio Sánchez
        $this->command->line('→ Estudios Sociales 1°–3° Grado...');
        $this->asignarBloque($id('04567890-1'), 'Estudios Sociales', [4, 5, 6], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000013-3'), 'Estudios Sociales', [4, 5, 6], ['C', 'D'], $ano->id);

        // 4°–6° Grado: A/B → Oscar Argueta | C/D → Isabel Orantes
        $this->command->line('→ Estudios Sociales 4°–6° Grado...');
        $this->asignarBloque($id('14567890-1'), 'Estudios Sociales', [7, 8, 9], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000016-6'), 'Estudios Sociales', [7, 8, 9], ['C', 'D'], $ano->id);

        // 7°–9° Grado: A/B → Verónica Chávez | C/D → Jaime Villacorta
        $this->command->line('→ Estudios Sociales 7°–9° Grado...');
        $this->asignarBloque($id('20000014-4'), 'Estudios Sociales', [10, 11, 12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000015-5'), 'Estudios Sociales', [10, 11, 12], ['C', 'D'], $ano->id);

        // ================================================================
        // INGLÉS  (solo 7°–9°, órdenes 10–12)
        // ================================================================

        // 7°–8° Grado: A/B → Sandra Portillo | C/D → Katherine Molina
        $this->command->line('→ Inglés 7°–8° Grado...');
        $this->asignarBloque($id('05678901-2'), 'Inglés', [10, 11], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000017-7'), 'Inglés', [10, 11], ['C', 'D'], $ano->id);

        // 9° Grado: A/B → Patricia Luna | C/D → Erick Alfaro
        $this->command->line('→ Inglés 9° Grado...');
        $this->asignarBloque($id('13456789-0'), 'Inglés', [12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000018-8'), 'Inglés', [12], ['C', 'D'], $ano->id);

        // ================================================================
        // INFORMÁTICA  (solo 7°–9°, órdenes 10–12)
        // ================================================================

        // 7°–8° Grado: A/B → Miguel Orellana | C/D → Darwin Melara
        $this->command->line('→ Informática 7°–8° Grado...');
        $this->asignarBloque($id('06789012-3'), 'Informática', [10, 11], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000019-9'), 'Informática', [10, 11], ['C', 'D'], $ano->id);

        // 9° Grado: A/B → Fátima Zelaya | C/D → Lorena Cisneros
        $this->command->line('→ Informática 9° Grado...');
        $this->asignarBloque($id('15678901-2'), 'Informática', [12], ['A', 'B'], $ano->id);
        $this->asignarBloque($id('20000020-0'), 'Informática', [12], ['C', 'D'], $ano->id);

        // ================================================================
        // EDUCACIÓN ARTÍSTICA  (1°–9°, órdenes 4–12)
        // ================================================================
        // Karla Ramírez → sección A todos los grados
        // Ana Parada    → sección B todos los grados
        // Luis Tobar    → secciones C y D todos los grados
        $this->command->line('→ Educación Artística...');
        $this->asignarBloque($id('07890123-4'), 'Educación Artística', range(4, 12), ['A'], $ano->id);
        $this->asignarBloque($id('20000021-1'), 'Educación Artística', range(4, 12), ['B'], $ano->id);
        $this->asignarBloque($id('20000022-2'), 'Educación Artística', range(4, 12), ['C', 'D'], $ano->id);

        // ================================================================
        // EDUCACIÓN FÍSICA  (1°–9°, órdenes 4–12)
        // ================================================================
        // Ernesto Montes   → sección A todos los grados
        // Wilfredo Mejía   → sección B todos los grados
        // Claudia Velásquez → secciones C y D todos los grados
        $this->command->line('→ Educación Física...');
        $this->asignarBloque($id('08901234-5'), 'Educación Física', range(4, 12), ['A'], $ano->id);
        $this->asignarBloque($id('20000023-3'), 'Educación Física', range(4, 12), ['B'], $ano->id);
        $this->asignarBloque($id('20000024-4'), 'Educación Física', range(4, 12), ['C', 'D'], $ano->id);

        // ================================================================
        // MORAL, URBANIDAD Y CÍVICA  (4°–9°, órdenes 7–12)
        // ================================================================
        // Rosa Peñate    → sección A todos los grados
        // Nora Cabrera   → sección B todos los grados
        // Rodrigo Flores → secciones C y D todos los grados
        $this->command->line('→ Moral, Urbanidad y Cívica...');
        $this->asignarBloque($id('09012345-6'), 'Moral, Urbanidad y Cívica', range(7, 12), ['A'], $ano->id);
        $this->asignarBloque($id('20000025-5'), 'Moral, Urbanidad y Cívica', range(7, 12), ['B'], $ano->id);
        $this->asignarBloque($id('20000026-6'), 'Moral, Urbanidad y Cívica', range(7, 12), ['C', 'D'], $ano->id);

        $this->command->info('✅ Asignaciones docentes creadas correctamente.');
    }
}
