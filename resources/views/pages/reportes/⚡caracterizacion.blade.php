<?php

use Livewire\Component;
use App\Models\Grado;
use App\Models\AnoLectivo;
use App\Models\Matricula;
use App\Models\Estudiante;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    public ?int $gradoId = null;
    public ?int $anioLectivoId = null;

    public array $datos = [];

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $ano = AnoLectivo::where('activo', true)->first();
        if ($ano) {
            $this->anioLectivoId = $ano->id;
        }

        $this->generar();
    }

    public function getGrados()
    {
        return Grado::orderBy('orden')->get();
    }

    public function updatedGradoId()
    {
        $this->generar();
    }

    public function generar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        if (!$this->anioLectivoId) {
            return;
        }

        $ano = AnoLectivo::findOrFail($this->anioLectivoId);

        $gradoNombre = 'Institucional (Todos los grados)';
        if ($this->gradoId) {
            $g = Grado::find($this->gradoId);
            $gradoNombre = $g ? $g->nombre : '';
        }

        // Fetch active enrollments
        $matriculas = Matricula::where('ano_lectivo_id', $this->anioLectivoId)
            ->where('estado', 'ACTIVA')
            ->whereHas('seccion', function ($q) {
                if ($this->gradoId) {
                    $q->where('grado_id', $this->gradoId);
                }
            })
            ->with(['estudiante.tutores'])
            ->get();

        $totalEstudiantes = $matriculas->count();
        $totalMales = 0;
        $totalFemales = 0;
        $totalDai = 0;
        $totalExtraedad = 0;
        $totalRepitentes = 0;

        $actividadesEconomicas = [];
        $convivencias = [];
        $tutoresAcademicos = [];
        $tutoresLaborales = [];

        foreach ($matriculas as $mat) {
            $est = $mat->estudiante;
            if (!$est) {
                continue;
            }

            // Gender
            if ($est->genero === 'M') {
                $totalMales++;
            } else {
                $totalFemales++;
            }

            // Special indicators
            if ($est->pertenece_dai) $totalDai++;
            if ($est->tiene_extraedad) $totalExtraedad++;
            if ($est->es_repitente) $totalRepitentes++;

            // Economic activity
            $act = $est->actividad_economica ?: 'No Especificada';
            $actividadesEconomicas[$act] = ($actividadesEconomicas[$act] ?? 0) + 1;

            // Family structure
            $conv = $est->convivencia ?: 'No Especificada';
            $convivencias[$conv] = ($convivencias[$conv] ?? 0) + 1;

            // Primary tutor details
            $tutor = $est->tutores->first(); // Default to first tutor
            $nivel = $tutor ? $tutor->nivel_academico : 'No Especificada';
            $sit = $tutor ? $tutor->situacion_laboral : 'No Especificada';

            $tutoresAcademicos[$nivel] = ($tutoresAcademicos[$nivel] ?? 0) + 1;
            $tutoresLaborales[$sit] = ($tutoresLaborales[$sit] ?? 0) + 1;
        }

        // Sort sub-arrays
        arsort($actividadesEconomicas);
        arsort($convivencias);
        arsort($tutoresAcademicos);
        arsort($tutoresLaborales);

        $this->datos = [
            'anio' => $ano->anio,
            'grado' => $gradoNombre,
            'total_estudiantes' => $totalEstudiantes,
            'total_males' => $totalMales,
            'total_females' => $totalFemales,
            'total_dai' => $totalDai,
            'total_extraedad' => $totalExtraedad,
            'total_repitentes' => $totalRepitentes,
            'actividades_economicas' => $actividadesEconomicas,
            'convivencias' => $convivencias,
            'tutores_academicos' => $tutoresAcademicos,
            'tutores_laborales' => $tutoresLaborales,
        ];
    }

    public function exportarPDF()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $pdf = Pdf::loadView('reportes.pdf-caracterizacion', ['datos' => $this->datos]);
        
        $nombreFichero = "caracterizacion_demografica_" . ($this->gradoId ? "grado_" . $this->gradoId : "institucional") . ".pdf";
        return response()->streamDownload(
            fn () => print($pdf->output()),
            $nombreFichero
        );
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Caracterización Demográfica | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Caracterización Demográfica</h1>
            <p class="mt-1 text-sm text-slate-500">Consulte estadísticas de perfil familiar, social y económico del alumnado.</p>
        </div>
        <div>
            <button 
                type="button"
                wire:click="exportarPDF"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Descargar PDF
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5">
        <div class="w-full md:w-80">
            <label for="carac_grado" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Filtrar por Grado Académico</label>
            <select 
                id="carac_grado"
                wire:model.live="gradoId"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Consolidado Institucional (Todos)</option>
                @foreach($this->getGrados() as $grado)
                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- General Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex items-center gap-4">
            <div class="bg-indigo-50 text-indigo-600 rounded-2xl p-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.12c0-.12-.007-.24-.022-.36M15 19.128C15 15.606 12.144 12.75 8.75 12.75c-3.394 0-6.25 2.856-6.25 6.25v.109a2.25 2.25 0 002.25 2.263h1.5a2.25 2.25 0 002.25-2.263v-.109m0-6.19c-.501.91-.786 1.957-.786 3.07M12.75 7.5a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
            </div>
            <div>
                <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wide">Total Matriculados</span>
                <span class="block text-xl font-black text-slate-900 mt-0.5">{{ $datos['total_estudiantes'] }} estudiantes</span>
                <span class="text-3xs text-slate-500 font-medium">H: {{ $datos['total_males'] }} | M: {{ $datos['total_females'] }}</span>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex items-center gap-4">
            <div class="bg-rose-50 text-rose-600 rounded-2xl p-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wide">Extraedad / Repitencia</span>
                <span class="block text-xl font-black text-slate-900 mt-0.5">Sobredad: {{ $datos['total_extraedad'] }}</span>
                <span class="text-3xs text-slate-500 font-medium">Repitentes: {{ $datos['total_repitentes'] }}</span>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex items-center gap-4">
            <div class="bg-emerald-50 text-emerald-600 rounded-2xl p-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0110 21a3.745 3.745 0 01-3.296-1.043A3.746 3.746 0 013.408 16.66 3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0114 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            </div>
            <div>
                <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wide">Atención Especial DAI</span>
                <span class="block text-xl font-black text-slate-900 mt-0.5">Total DAI: {{ $datos['total_dai'] }}</span>
                <span class="text-3xs text-slate-500 font-medium">Con necesidades especiales de apoyo</span>
            </div>
        </div>
    </div>

    <!-- Tables Cross Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Económica -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Actividad Económica Estudiantes</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <tbody class="divide-y divide-slate-100">
                    @forelse($datos['actividades_economicas'] as $act => $count)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-700 font-medium">{{ $act }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-slate-900 text-right">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-slate-400">Sin registros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Convivencia -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Estructura de Convivencia Familiar</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <tbody class="divide-y divide-slate-100">
                    @forelse($datos['convivencias'] as $conv => $count)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-700 font-medium">{{ $conv }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-slate-900 text-right">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-slate-400">Sin registros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tutor Académico -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nivel Académico de los Tutores</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <tbody class="divide-y divide-slate-100">
                    @forelse($datos['tutores_academicos'] as $nivel => $count)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-700 font-medium">{{ $nivel }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-slate-900 text-right">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-slate-400">Sin registros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tutor Laboral -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Situación Laboral de los Tutores</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <tbody class="divide-y divide-slate-100">
                    @forelse($datos['tutores_laborales'] as $sit => $count)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-700 font-medium">{{ $sit }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-slate-900 text-right">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-slate-400">Sin registros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
