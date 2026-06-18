<?php

use Livewire\Component;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use App\Models\Matricula;
use App\Models\RegistroNota;
use App\Models\InformeRendimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $seccionId = null;
    public ?int $trimestre = 1; // 1, 2, 3 or null (null = Anual)

    public ?InformeRendimiento $informe = null;
    public string $observaciones = '';

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $ano = AnoLectivo::where('activo', true)->first();
        if ($ano) {
            $firstSec = Seccion::where('ano_lectivo_id', $ano->id)->first();
            if ($firstSec) {
                $this->seccionId = $firstSec->id;
            }
        }

        $this->cargarInforme();
    }

    public function getSecciones()
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }
        return Seccion::where('ano_lectivo_id', $ano->id)->with('grado')->get();
    }

    public function updatedSeccionId()
    {
        $this->cargarInforme();
    }

    public function updatedTrimestre()
    {
        $this->cargarInforme();
    }

    public function cargarInforme()
    {
        $this->informe = null;
        $this->observaciones = '';

        if (!$this->seccionId) {
            return;
        }

        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return;
        }

        $existente = InformeRendimiento::where('seccion_id', $this->seccionId)
            ->where('ano_lectivo_id', $ano->id)
            ->where('trimestre', $this->trimestre ?: null)
            ->first();

        if ($existente) {
            $this->informe = $existente;
            $this->observaciones = $existente->observaciones ?? '';
        }
    }

    public function generar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        if (!$this->seccionId) {
            $this->dispatch('notify', message: 'Seleccione una sección académica.', type: 'error');
            return;
        }

        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            $this->dispatch('notify', message: 'No hay un año lectivo activo.', type: 'error');
            return;
        }

        // Matrícula histórica inicial registrada
        $matriculaInicial = Matricula::where('seccion_id', $this->seccionId)->count();

        // Estudiantes activos en el sistema
        $matriculaActual = Matricula::where('seccion_id', $this->seccionId)->where('estado', 'ACTIVA')->count();

        // Desertores (retirados, trasladados o inactivos)
        $desertores = Matricula::where('seccion_id', $this->seccionId)
            ->whereIn('estado', ['RETIRADO', 'TRASLADADO', 'INACTIVA'])
            ->count();

        // Alumnos activos en situación de extraedad
        $sobredad = Matricula::where('seccion_id', $this->seccionId)
            ->where('estado', 'ACTIVA')
            ->whereHas('estudiante', fn($q) => $q->where('tiene_extraedad', true))
            ->count();

        // Alumnos activos con condición de repitente
        $repitentes = Matricula::where('seccion_id', $this->seccionId)
            ->where('estado', 'ACTIVA')
            ->whereHas('estudiante', fn($q) => $q->where('es_repitente', true))
            ->count();

        // Determinar aprobados y reprobados por género
        $aprobadosM = 0;
        $aprobadosF = 0;
        $reprobadosM = 0;
        $reprobadosF = 0;

        $matriculasActivas = Matricula::where('seccion_id', $this->seccionId)
            ->where('estado', 'ACTIVA')
            ->with('estudiante')
            ->get();

        foreach ($matriculasActivas as $mat) {
            $queryNotas = RegistroNota::where('matricula_id', $mat->id)
                ->when($this->trimestre, fn($q) => $q->where('trimestre', $this->trimestre));

            $notas = $queryNotas->pluck('nota_final');

            $promedio = 0.0;
            if ($notas->isNotEmpty()) {
                // Filter nulls just in case, default to 0 if null
                $promedio = $notas->avg() ?: 0.0;
            }

            $genero = $mat->estudiante->genero;

            if ($promedio >= 5.0) {
                if ($genero === 'M') {
                    $aprobadosM++;
                } else {
                    $aprobadosF++;
                }
            } else {
                if ($genero === 'M') {
                    $reprobadosM++;
                } else {
                    $reprobadosF++;
                }
            }
        }

        DB::transaction(function () use ($ano, $matriculaInicial, $matriculaActual, $aprobadosM, $aprobadosF, $reprobadosM, $reprobadosF, $desertores, $sobredad, $repitentes) {
            $this->informe = InformeRendimiento::updateOrCreate([
                'seccion_id' => $this->seccionId,
                'ano_lectivo_id' => $ano->id,
                'trimestre' => $this->trimestre ?: null,
            ], [
                'matricula_inicial' => $matriculaInicial,
                'matricula_actual' => $matriculaActual,
                'aprobados_m' => $aprobadosM,
                'aprobados_f' => $aprobadosF,
                'reprobados_m' => $reprobadosM,
                'reprobados_f' => $reprobadosF,
                'desertores' => $desertores,
                'sobredad' => $sobredad,
                'repitentes' => $repitentes,
                'generado_por' => auth()->id(),
            ]);
        });

        $this->observaciones = $this->informe->observaciones ?? '';
        $this->dispatch('notify', message: 'Informe generado e inicializado.', type: 'success');
    }

    public function guardarObservaciones()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        if (!$this->informe) {
            return;
        }

        $this->informe->observaciones = $this->observaciones ?: null;
        $this->informe->save();

        $this->dispatch('notify', message: 'Observaciones guardadas con éxito.', type: 'success');
    }

    public function exportarPDF()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        if (!$this->informe) {
            return;
        }

        // Eager load relationships for PDF rendering
        $informeCompleto = InformeRendimiento::with(['seccion.grado', 'anoLectivo'])->findOrFail($this->informe->id);

        $pdf = Pdf::loadView('reportes.pdf-rendimiento', ['informe' => $informeCompleto]);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "informe_rendimiento_seccion_" . str_replace(' ', '_', $informeCompleto->seccion->letra) . ".pdf"
        );
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Informe de Rendimiento | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Rendimiento Académico</h1>
            <p class="mt-1 text-sm text-slate-500">Genere informes consolidados de matrícula, aprobados y reprobados requeridos por el MINED.</p>
        </div>
        <div>
            @if($informe)
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
            @endif
        </div>
    </div>

    <!-- Filters & Form -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex flex-col md:flex-row items-end gap-4">
        <div class="w-full md:w-64">
            <label for="perf_seccion" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sección Académica</label>
            <select 
                id="perf_seccion"
                wire:model.live="seccionId"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Seleccione una sección</option>
                @foreach($this->getSecciones() as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->nombre_completo }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-48">
            <label for="perf_trim" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Período / Trimestre</label>
            <select 
                id="perf_trim"
                wire:model.live="trimestre"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="1">Trimestre 1</option>
                <option value="2">Trimestre 2</option>
                <option value="3">Trimestre 3</option>
                <option value="">Anual (Consolidado)</option>
            </select>
        </div>

        <div>
            <button 
                type="button"
                wire:click="generar"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
            >
                {{ $informe ? 'Recalcular Informe' : 'Generar Informe' }}
            </button>
        </div>
    </div>

    @if($informe)
        <!-- Report content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Pane: Data Grid -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Enrolment metrics card -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Resumen de Matrícula</h3>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-slate-50 rounded-2xl p-4 text-center">
                            <span class="block text-2xs text-slate-400 font-bold uppercase tracking-wide">Matrícula Inicial</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ $informe->matricula_inicial }}</span>
                        </div>
                        <div class="bg-indigo-50 rounded-2xl p-4 text-center border border-indigo-100">
                            <span class="block text-2xs text-indigo-500 font-bold uppercase tracking-wide">Matrícula Actual</span>
                            <span class="block text-2xl font-black text-indigo-700 mt-1">{{ $informe->matricula_actual }}</span>
                        </div>
                        <div class="bg-rose-50 rounded-2xl p-4 text-center border border-rose-100">
                            <span class="block text-2xs text-rose-500 font-bold uppercase tracking-wide">Desertores / Retiros</span>
                            <span class="block text-2xl font-black text-rose-700 mt-1">{{ $informe->desertores }}</span>
                        </div>
                    </div>
                </div>

                <!-- Academic Results card -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Resultados Académicos</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Approved Card -->
                        <div class="border border-slate-200 rounded-3xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-emerald-700">Total Aprobados</span>
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $informe->matricula_actual > 0 ? round((($informe->aprobados_m + $informe->aprobados_f) / $informe->matricula_actual) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <span class="block text-4xl font-black text-emerald-600">{{ $informe->aprobados_m + $informe->aprobados_f }}</span>
                            <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 pt-3">
                                <div>
                                    <span class="block text-slate-400 font-medium">Hombres (M):</span>
                                    <span class="font-bold text-slate-800">{{ $informe->aprobados_m }}</span>
                                </div>
                                <div>
                                    <span class="block text-slate-400 font-medium">Mujeres (F):</span>
                                    <span class="font-bold text-slate-800">{{ $informe->aprobados_f }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Card -->
                        <div class="border border-slate-200 rounded-3xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-rose-700">Total Reprobados</span>
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    {{ $informe->matricula_actual > 0 ? round((($informe->reprobados_m + $informe->reprobados_f) / $informe->matricula_actual) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <span class="block text-4xl font-black text-rose-600">{{ $informe->reprobados_m + $informe->reprobados_f }}</span>
                            <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 pt-3">
                                <div>
                                    <span class="block text-slate-400 font-medium">Hombres (M):</span>
                                    <span class="font-bold text-slate-800">{{ $informe->reprobados_m }}</span>
                                </div>
                                <div>
                                    <span class="block text-slate-400 font-medium">Mujeres (F):</span>
                                    <span class="font-bold text-slate-800">{{ $informe->reprobados_f }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Pane: Indicators and Director Observations -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Indicators card -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Indicadores Especiales</h3>
                    
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <span class="block text-xs font-semibold text-slate-700">Alumnos en Extraedad</span>
                            <span class="text-3xs text-slate-400">Edad mayor a la correspondiente</span>
                        </div>
                        <span class="text-lg font-black text-slate-900">{{ $informe->sobredad }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-slate-700">Alumnos Repitentes</span>
                            <span class="text-3xs text-slate-400">Cursando por segunda vez+</span>
                        </div>
                        <span class="text-lg font-black text-slate-900">{{ $informe->repitentes }}</span>
                    </div>
                </div>

                <!-- Observations card -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Observaciones de la Dirección</h3>
                    
                    <textarea 
                        id="obs_text"
                        wire:model="observaciones"
                        rows="4"
                        placeholder="Escriba comentarios u observaciones del trimestre/año lectivo para el MINED..."
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-xs transition"
                    ></textarea>
                    
                    <button 
                        type="button"
                        wire:click="guardarObservaciones"
                        class="w-full inline-flex justify-center rounded-xl bg-slate-900 hover:bg-slate-800 py-2.5 text-xs font-bold text-white shadow-xs transition"
                    >
                        Guardar Observaciones
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-10 text-center text-slate-500">
            Seleccione una sección y haga click en "Generar Informe" para cargar las estadísticas académicas.
        </div>
    @endif
</div>
