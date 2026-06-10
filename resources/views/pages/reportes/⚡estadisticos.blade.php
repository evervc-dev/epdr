<?php

use Livewire\Component;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\Producto;
use App\Models\AnoLectivo;
use App\Models\ReporteEstadistico;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $tipo = 'caracterizacion';
    public string $titulo = '';

    // Parameters container
    public array $parametros = [];

    // Filter list providers
    public ?int $paramGradoId = null;
    public ?int $paramSeccionId = null;
    public ?int $paramTrimestre = 1;
    public string $paramFechaDesde = '';
    public string $paramFechaHasta = '';
    public ?int $paramProductoId = null;

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $this->paramFechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->paramFechaHasta = now()->format('Y-m-d');
    }

    public function getGrados()
    {
        return Grado::orderBy('orden')->get();
    }

    public function getSecciones()
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }
        return Seccion::where('ano_lectivo_id', $ano->id)->with('grado')->get();
    }

    public function getProductos()
    {
        return Producto::where('activo', true)->orderBy('nombre')->get();
    }

    public function getHistorial()
    {
        return ReporteEstadistico::with('generador')
            ->latest()
            ->get();
    }

    public function updatedTipo()
    {
        // Reset specific fields
        $this->paramGradoId = null;
        $this->paramSeccionId = null;
        $this->paramTrimestre = 1;
        $this->paramProductoId = null;
    }

    public function generar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        // Build parameters array and title based on type
        $params = [];
        $titleSuffix = '';

        if ($this->tipo === 'caracterizacion') {
            $params['grado_id'] = $this->paramGradoId;
            if ($this->paramGradoId) {
                $g = Grado::find($this->paramGradoId);
                $titleSuffix = " para " . ($g ? $g->nombre : 'Grado');
            } else {
                $titleSuffix = " Consolidado Institucional";
            }
            $tituloReporte = "Análisis de Caracterización Demográfica" . $titleSuffix;
        } 
        elseif ($this->tipo === 'rendimiento') {
            $params['seccion_id'] = $this->paramSeccionId;
            $params['trimestre'] = $this->paramTrimestre;
            
            $sec = Seccion::find($this->paramSeccionId);
            $titleSuffix = " - " . ($sec ? $sec->nombre_completo : 'Sección');
            $titleSuffix .= $this->paramTrimestre ? " (Trimestre {$this->paramTrimestre})" : " (Anual)";
            
            $tituloReporte = "Informe de Rendimiento Académico" . $titleSuffix;
        } 
        elseif ($this->tipo === 'asistencias') {
            $params['seccion_id'] = $this->paramSeccionId;
            $params['fecha_desde'] = $this->paramFechaDesde;
            $params['fecha_hasta'] = $this->paramFechaHasta;
            
            $sec = Seccion::find($this->paramSeccionId);
            $titleSuffix = " - " . ($sec ? $sec->nombre_completo : 'Sección');
            $titleSuffix .= " del {$this->paramFechaDesde} al {$this->paramFechaHasta}";
            
            $tituloReporte = "Métricas Generales de Asistencia" . $titleSuffix;
        } 
        else { // inventario
            $params['producto_id'] = $this->paramProductoId;
            $params['fecha_desde'] = $this->paramFechaDesde;
            $params['fecha_hasta'] = $this->paramFechaHasta;
            
            $prod = Producto::find($this->paramProductoId);
            $titleSuffix = " - " . ($prod ? $prod->nombre : 'Todos los productos');
            $titleSuffix .= " del {$this->paramFechaDesde} al {$this->paramFechaHasta}";
            
            $tituloReporte = "Auditoría de Movimiento de Alimentos" . $titleSuffix;
        }

        DB::transaction(function () use ($tituloReporte, $params) {
            ReporteEstadistico::create([
                'titulo' => $tituloReporte,
                'tipo' => $this->tipo,
                'parametros' => $params, // Casts array to JSON automatically
                'generado_por' => auth()->id(),
            ]);
        });

        $this->dispatch('notify', message: 'Reporte registrado en el historial de auditoría.', type: 'success');

        // Redirect to actual report views passing correct parameters
        if ($this->tipo === 'caracterizacion') {
            return redirect()->route('reportes.caracterizacion', ['gradoId' => $this->paramGradoId]);
        } 
        elseif ($this->tipo === 'rendimiento') {
            return redirect()->route('reportes.rendimiento', ['seccionId' => $this->paramSeccionId, 'trimestre' => $this->paramTrimestre]);
        } 
        elseif ($this->tipo === 'asistencias') {
            return redirect()->route('asistencias.reporte', ['seccionId' => $this->paramSeccionId, 'fechaDesde' => $this->paramFechaDesde, 'fechaHasta' => $this->paramFechaHasta]);
        } 
        else {
            return redirect()->route('reportes.inventario', ['productoId' => $this->paramProductoId, 'fechaDesde' => $this->paramFechaDesde, 'fechaHasta' => $this->paramFechaHasta]);
        }
    }

    public function eliminar($id)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);
        ReporteEstadistico::destroy($id);
        $this->dispatch('notify', message: 'Registro del historial eliminado.', type: 'info');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Generador de Reportes Estadísticos | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Reportes Estadísticos</h1>
            <p class="mt-1 text-sm text-slate-500">Configure, genere y registre reportes a medida para auditorías y toma de decisiones.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configure Report Form -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 lg:col-span-1 h-fit space-y-4">
            <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Configurar Reporte</h2>
            
            <form wire:submit.prevent="generar" class="space-y-4">
                <!-- Tipo de Reporte -->
                <div>
                    <label for="rep_tipo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Análisis</label>
                    <select 
                        id="rep_tipo"
                        wire:model.live="tipo"
                        class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    >
                        <option value="caracterizacion">Caracterización Demográfica</option>
                        <option value="rendimiento">Rendimiento Académico</option>
                        <option value="asistencias">Control de Asistencias</option>
                        <option value="inventario">Inventario / Bodega</option>
                    </select>
                </div>

                <!-- Parameters according to type -->
                @if($tipo === 'caracterizacion')
                    <div>
                        <label for="rep_grado" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Grado Académico</label>
                        <select 
                            id="rep_grado"
                            wire:model="paramGradoId"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="">Consolidado Institucional (Todos)</option>
                            @foreach($this->getGrados() as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($tipo === 'rendimiento')
                    <div>
                        <label for="rep_secc" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sección Académica</label>
                        <select 
                            id="rep_secc"
                            wire:model="paramSeccionId"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        >
                            <option value="">Seleccione una sección</option>
                            @foreach($this->getSecciones() as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="rep_trim" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Período / Trimestre</label>
                        <select 
                            id="rep_trim"
                            wire:model="paramTrimestre"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="1">Trimestre 1</option>
                            <option value="2">Trimestre 2</option>
                            <option value="3">Trimestre 3</option>
                            <option value="">Anual</option>
                        </select>
                    </div>
                @endif

                @if($tipo === 'asistencias')
                    <div>
                        <label for="rep_secc_as" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sección Académica</label>
                        <select 
                            id="rep_secc_as"
                            wire:model="paramSeccionId"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        >
                            <option value="">Seleccione una sección</option>
                            @foreach($this->getSecciones() as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="rep_desde" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Desde</label>
                        <input 
                            id="rep_desde"
                            type="date" 
                            wire:model="paramFechaDesde"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        />
                    </div>
                    <div>
                        <label for="rep_hasta" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Hasta</label>
                        <input 
                            id="rep_hasta"
                            type="date" 
                            wire:model="paramFechaHasta"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        />
                    </div>
                @endif

                @if($tipo === 'inventario')
                    <div>
                        <label for="rep_prod" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Producto Alimenticio</label>
                        <select 
                            id="rep_prod"
                            wire:model="paramProductoId"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="">Todos los productos</option>
                            @foreach($this->getProductos() as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="rep_desde_inv" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Desde</label>
                        <input 
                            id="rep_desde_inv"
                            type="date" 
                            wire:model="paramFechaDesde"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        />
                    </div>
                    <div>
                        <label for="rep_hasta_inv" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Hasta</label>
                        <input 
                            id="rep_hasta_inv"
                            type="date" 
                            wire:model="paramFechaHasta"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            required
                        />
                    </div>
                @endif

                <button 
                    type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                >
                    Generar Reporte
                </button>
            </form>
        </div>

        <!-- History of Generated Reports -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden lg:col-span-2">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Historial de Reportes Generados</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Título del Reporte</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Tipo</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Fecha Registro</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Generador por</th>
                            <th scope="col" class="relative px-6 py-3.5 text-right">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($this->getHistorial() as $hist)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900">
                                    {{ $hist->titulo }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border bg-indigo-50 text-indigo-700 border-indigo-200">
                                        {{ $hist->tipo }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $hist->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $hist->generador?->name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <button 
                                        type="button" 
                                        wire:click="eliminar({{ $hist->id }})"
                                        class="text-rose-600 hover:text-rose-900 transition"
                                    >
                                        Borrar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    No hay registros en el historial de reportes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
