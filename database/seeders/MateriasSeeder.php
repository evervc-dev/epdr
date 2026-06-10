<?php

namespace Database\Seeders;

use App\Models\Grado;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materiasPorGrado = [
            // Grados 1–3
            1 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
            2 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
            3 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Educación Artística', 'Educación Física'],
            // Grados 4–6
            4 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
            5 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
            6 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
            // Grados 7–9
            7 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Inglés', 'Informática', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
            8 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Inglés', 'Informática', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
            9 => ['Lenguaje', 'Matemática', 'Ciencias Naturales', 'Estudios Sociales', 'Inglés', 'Informática', 'Moral, Urbanidad y Cívica', 'Educación Artística', 'Educación Física'],
        ];

        // Map to get unique prefix codes for subjects
        $prefixMap = [
            'Lenguaje' => 'LEN',
            'Matemática' => 'MAT',
            'Ciencias Naturales' => 'CIE',
            'Estudios Sociales' => 'SOC',
            'Educación Artística' => 'ART',
            'Educación Física' => 'FIS',
            'Moral, Urbanidad y Cívica' => 'MOR',
            'Inglés' => 'ING',
            'Informática' => 'INF',
        ];

        foreach ($materiasPorGrado as $gradoOrden => $materias) {
            // +3 because Parvularia 4, 5, 6 take up orders 1, 2, 3
            $grado = Grado::where('orden', $gradoOrden + 3)->first();

            if (! $grado) {
                continue;
            }

            foreach ($materias as $materiaNombre) {
                $prefix = array_key_exists($materiaNombre, $prefixMap) ? $prefixMap[$materiaNombre] : strtoupper(substr($materiaNombre, 0, 3));
                $codigo = $prefix.str_pad((string) $gradoOrden, 2, '0', STR_PAD_LEFT);

                Materia::firstOrCreate([
                    'codigo' => $codigo,
                ], [
                    'nombre' => $materiaNombre,
                    'grado_id' => $grado->id,
                ]);
            }
        }
    }
}
