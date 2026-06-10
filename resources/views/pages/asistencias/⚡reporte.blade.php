<?php
 
use Livewire\Component;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use App\Models\Matricula;
use App\Models\Asistencia;
use App\Models\Materia;
use Illuminate\Support\Carbon;
 
new class extends Component
{
    public ?int $seccionId = null;
    public ?int $materiaId = null;
    public string $fechaDesde = '';
    public string $fechaHasta = '';
    public array $resumen = [];

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');

        $ano = AnoLectivo::where('activo', true)->first();
        if ($ano) {
            $firstSecc = Seccion::where('ano_lectivo_id', $ano->id)->first();
            if ($firstSecc) {
                $this->seccionId = $firstSecc->id;
            }
        }

        $this->generar();
    }

    public function updatedSeccionId()
    {
        $this->materiaId = null;
        $this->generar();
    }

    public function getSecciones()
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }

        return Seccion::where('ano_lectivo_id', $ano->id)->with('grado')->get();
    }

    public function getMaterias()
    {
        if (!$this->seccionId) {
            return collect();
        }

        $seccion = Seccion::find($this->seccionId);
        if (!$seccion) {
            return collect();
        }

        return Materia::where('grado_id', $seccion->grado_id)->get()->sortBy('nombre');
    }

    public function generar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $this->resumen = [];

        if (!$this->seccionId || !$this->fechaDesde || !$this->fechaHasta) {
            return;
        }

        $matriculas = Matricula::where('seccion_id', $this->seccionId)
            ->with('estudiante')
            ->get()
            ->sortBy(fn($m) => $m->estudiante->apellidos . ' ' . $m->estudiante->nombres);

        $desde = Carbon::parse($this->fechaDesde)->startOfDay();
        $hasta = Carbon::parse($this->fechaHasta)->endOfDay();

        foreach ($matriculas as $mat) {
            $asistencias = Asistencia::where('matricula_id', $mat->id)
                ->whereBetween('fecha', [$desde, $hasta])
                ->when($this->materiaId, fn($q) => $q->where('materia_id', $this->materiaId))
                ->get();

            $p = $asistencias->where('estado', 'P')->count();
            $a = $asistencias->where('estado', 'A')->count();
            $j = $asistencias->where('estado', 'J')->count();
            $total = $asistencias->count();

            $porcentaje = $total > 0 ? round(($p / $total) * 100, 1) : 100.0;

            $this->resumen[] = [
                'matricula_id' => $mat->id,
                'nie' => $mat->estudiante->nie,
                'nombre' => $mat->estudiante->nombre_completo,
                'presentes' => $p,
                'ausentes' => $a,
                'justificados' => $j,
                'total' => $total,
                'porcentaje' => $porcentaje,
            ];
        }
    }

    public function exportar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $seccion = Seccion::with('grado')->find($this->seccionId);
        $nombreSeccion = $seccion ? $seccion->nombre_completo : 'Seccion';

        $nombreMateria = '';
        if ($this->materiaId) {
            $materia = Materia::find($this->materiaId);
            if ($materia) {
                $nombreMateria = '_' . $materia->nombre;
            }
        }

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=reporte_asistencia_" . str_replace(' ', '_', $nombreSeccion . $nombreMateria) . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['NIE', 'Estudiante', 'Presentes', 'Ausentes', 'Justificados', 'Total Días', 'Porcentaje Asistencia']);

            foreach ($this->resumen as $row) {
                fputcsv($file, [
                    $row['nie'],
                    $row['nombre'],
                    $row['presentes'],
                    $row['ausentes'],
                    $row['justificados'],
                    $row['total'],
                    $row['porcentaje'] . '%',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Reporte de Asistencia | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Reporte de Asistencias</h1>
            <p class="mt-1 text-sm text-slate-500">Consulte estadísticas y porcentajes de asistencia por sección y rango de fechas.</p>
        </div>
        <div>
            @if(count($resumen) > 0)
                <button 
                    type="button"
                    wire:click="exportar"
                    class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-350 shadow-2xs transition"
                >
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Exportar Reporte
                </button>
            @endif
        </div>
    </div>

    <!-- Filters panel -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5">
        <form wire:submit.prevent="generar" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- Sección Select -->
            <div>
                <label for="form_seccion" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sección Académica</label>
                <select 
                    id="form_seccion"
                    wire:model.live="seccionId"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                >
                    <option value="">Seleccione una sección...</option>
                    @foreach($this->getSecciones() as $secc)
                        <option value="{{ $secc->id }}">{{ $secc->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Materia Select (Opcional) -->
            <div>
                <label for="form_materia" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Materia (Opcional)</label>
                <select 
                    id="form_materia"
                    wire:model.live="materiaId"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                >
                    <option value="">Todas las materias</option>
                    @foreach($this->getMaterias() as $mat)
                        <option value="{{ $mat->id }}">{{ $mat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Desde input -->
            <div>
                <label for="form_desde" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Desde</label>
                <input 
                    type="date" 
                    id="form_desde"
                    wire:model="fechaDesde"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                />
            </div>

            <!-- Hasta input -->
            <div>
                <label for="form_hasta" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Hasta</label>
                <input 
                    type="date" 
                    id="form_hasta"
                    wire:model="fechaHasta"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                />
            </div>

            <!-- Generate Button -->
            <div>
                <button 
                    type="submit"
                    class="w-full flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                >
                    Generar Reporte
                </button>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    @if(count($resumen) > 0)
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-4 w-72">Estudiante</th>
                            <th class="px-6 py-4 text-center w-28">P</th>
                            <th class="px-6 py-4 text-center w-28">A</th>
                            <th class="px-6 py-4 text-center w-28">J</th>
                            <th class="px-6 py-4 text-center w-28">Total</th>
                            <th class="px-6 py-4 w-60">Porcentaje Asistencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($resumen as $row)
                            <tr class="hover:bg-slate-50/15 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">{{ $row['nombre'] }}</div>
                                    <div class="text-xs text-slate-450">NIE: {{ $row['nie'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-emerald-600 bg-emerald-50/10">
                                    {{ $row['presentes'] }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-rose-600 bg-rose-50/10">
                                    {{ $row['ausentes'] }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-amber-600 bg-amber-50/10">
                                    {{ $row['justificados'] }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-slate-700">
                                    {{ $row['total'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs">
                                    <div class="flex items-center gap-3">
                                        <!-- Colorful text indicator -->
                                        @if($row['porcentaje'] >= 90)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                                {{ $row['porcentaje'] }}%
                                            </span>
                                        @elseif($row['porcentaje'] >= 80)
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 font-bold text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                                {{ $row['porcentaje'] }}%
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 font-bold text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                                {{ $row['porcentaje'] }}%
                                            </span>
                                        @endif
                                        
                                        <!-- Mini progress bar -->
                                        <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden shrink-0">
                                            @if($row['porcentaje'] >= 90)
                                                <div class="bg-emerald-505 bg-emerald-500 h-full rounded-full" style="width: {{ $row['porcentaje'] }}%"></div>
                                            @elseif($row['porcentaje'] >= 80)
                                                <div class="bg-amber-500 h-full rounded-full" style="width: {{ $row['porcentaje'] }}%"></div>
                                            @else
                                                <div class="bg-rose-500 h-full rounded-full" style="width: {{ $row['porcentaje'] }}%"></div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($seccionId)
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-10 text-center text-slate-400 italic text-sm">
            Haga clic en Generar Reporte para cargar los datos de asistencia.
        </div>
    @endif
</div>
