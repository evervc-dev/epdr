<?php

use Livewire\Component;
use App\Models\Producto;
use App\Models\LoteAlimento;
use App\Models\MovimientoInventario;
use App\Models\DetalleAuditoriaBodega;
use App\Models\AuditoriaBodega;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

new class extends Component
{
    public string $fechaDesde = '';
    public string $fechaHasta = '';
    public ?int $productoId = null;

    public array $datos = [];

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'bodega']), 403);

        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');

        $this->generar();
    }

    public function getProductos()
    {
        return Producto::where('activo', true)->orderBy('nombre')->get();
    }

    public function updatedFechaDesde()
    {
        $this->generar();
    }

    public function updatedFechaHasta()
    {
        $this->generar();
    }

    public function updatedProductoId()
    {
        $this->generar();
    }

    public function generar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'bodega']), 403);

        $productoNombre = 'Todos los productos';
        if ($this->productoId) {
            $p = Producto::find($this->productoId);
            $productoNombre = $p ? $p->nombre : '';
        }

        // Obtener el estado actual de los lotes
        $lotesQuery = LoteAlimento::with('producto')
            ->when($this->productoId, fn($q) => $q->where('producto_id', $this->productoId));
        
        $lotesData = [];
        foreach ($lotesQuery->get() as $l) {
            $lotesData[] = [
                'codigo_lote' => $l->codigo_lote,
                'producto_nombre' => $l->producto->nombre,
                'fecha_ingreso' => $l->fecha_ingreso->format('d/m/Y'),
                'peso_total_kg' => $l->peso_total_kg,
                'stock_actual' => $l->stock_actual,
                'unidad' => $l->producto->unidad_peso,
            ];
        }

        // Consultar los movimientos dentro del período
        $desde = Carbon::parse($this->fechaDesde)->startOfDay();
        $hasta = Carbon::parse($this->fechaHasta)->endOfDay();

        $movsQuery = MovimientoInventario::with(['lote.producto', 'registrador'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($this->productoId, function($q) {
                $q->whereHas('lote', fn($ql) => $ql->where('producto_id', $this->productoId));
            });

        $movsData = [];
        foreach ($movsQuery->latest('fecha')->get() as $m) {
            $movsData[] = [
                'fecha' => $m->fecha->format('d/m/Y'),
                'producto_nombre' => $m->lote->producto->nombre,
                'codigo_lote' => $m->lote->codigo_lote,
                'tipo' => $m->tipo_movimiento,
                'cantidad' => $m->cantidad,
                'unidad' => $m->unidad,
                'usuario' => $m->registrador?->name ?? 'Sistema',
                'observaciones' => $m->observaciones ?? '',
            ];
        }

        // Cargar discrepancias reportadas en las auditorías
        $diffsQuery = DetalleAuditoriaBodega::with(['lote.producto', 'auditoria'])
            ->whereHas('auditoria', function($q) use ($desde, $hasta) {
                $q->where('estado', 'cerrada')
                  ->whereBetween('fecha_auditoria', [$desde, $hasta]);
            })
            ->when($this->productoId, function($q) {
                $q->whereHas('lote', fn($ql) => $ql->where('producto_id', $this->productoId));
            })
            ->where('diferencia', '!=', 0);

        $diffsData = [];
        foreach ($diffsQuery->get() as $d) {
            $diffsData[] = [
                'fecha' => $d->auditoria->fecha_auditoria->format('d/m/Y'),
                'codigo_lote' => $d->lote->codigo_lote,
                'producto_nombre' => $d->lote->producto->nombre,
                'stock_sistema' => $d->stock_sistema,
                'stock_fisico' => $d->stock_fisico,
                'diferencia' => $d->diferencia,
                'unidad' => $d->lote->producto->unidad_peso,
            ];
        }

        $this->datos = [
            'fecha_desde' => Carbon::parse($this->fechaDesde)->format('d/m/Y'),
            'fecha_hasta' => Carbon::parse($this->fechaHasta)->format('d/m/Y'),
            'producto_nombre' => $productoNombre,
            'lotes' => $lotesData,
            'movimientos' => $movsData,
            'diferencias' => $diffsData,
        ];
    }

    public function exportarPDF()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'bodega']), 403);

        $pdf = Pdf::loadView('reportes.pdf-inventario', ['datos' => $this->datos]);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "reporte_inventario_alimentos.pdf"
        );
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Reporte de Inventario | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Informe de Inventario</h1>
            <p class="mt-1 text-sm text-slate-500">Genere reportes de stock, movimientos y auditorías de bodega escolar.</p>
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

    <!-- Filters -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex flex-col md:flex-row items-end gap-4">
        <div class="w-full md:w-48">
            <label for="inv_desde" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Desde</label>
            <input 
                id="inv_desde"
                type="date" 
                wire:model.live="fechaDesde"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            />
        </div>

        <div class="w-full md:w-48">
            <label for="inv_hasta" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Hasta</label>
            <input 
                id="inv_hasta"
                type="date" 
                wire:model.live="fechaHasta"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            />
        </div>

        <div class="w-full md:w-64">
            <label for="inv_prod" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Filtrar por Producto</label>
            <select 
                id="inv_prod"
                wire:model.live="productoId"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Todos los productos</option>
                @foreach($this->getProductos() as $p)
                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- State of lotes card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Estado y Stock Actual de Lotes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Código Lote</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Producto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Fecha Ingreso</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Peso Recibido</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Stock Actual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($datos['lotes'] as $l)
                        <tr>
                            <td class="px-6 py-3 text-sm font-mono font-bold text-slate-900">{{ $l['codigo_lote'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-800">{{ $l['producto_nombre'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-500">{{ $l['fecha_ingreso'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-550 text-right">{{ number_format($l['peso_total_kg'], 2) }} {{ $l['unidad'] }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-indigo-650 text-right">{{ number_format($l['stock_actual'], 2) }} {{ $l['unidad'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-4 text-center text-sm text-slate-400">No hay lotes para mostrar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Movements card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">2. Movimientos de Bodega en el Período</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Producto / Lote</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Cantidad</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Registrado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($datos['movimientos'] as $m)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-500">{{ $m['fecha'] }}</td>
                            <td class="px-6 py-3 text-sm">
                                <div class="font-bold text-slate-800">{{ $m['producto_nombre'] }}</div>
                                <div class="font-mono text-2xs text-slate-450">Lote: {{ $m['codigo_lote'] }}</div>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                @if($m['tipo'] === 'entrada')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-2xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Entrada</span>
                                @elseif($m['tipo'] === 'salida')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-2xs font-bold bg-rose-50 text-rose-700 border border-rose-100">Salida</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-2xs font-bold bg-amber-50 text-amber-700 border border-amber-100">Merma</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-right {{ $m['tipo'] === 'entrada' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $m['tipo'] === 'entrada' ? '+' : '-' }}{{ number_format($m['cantidad'], 2) }} {{ $m['unidad'] }}
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-500">{{ $m['usuario'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-4 text-center text-sm text-slate-400">No hay movimientos registrados en este rango de fechas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Differences card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">3. Discrepancias / Ajustes de Auditorías Recientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Fecha Auditoría</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Código Lote</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Producto</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Stock Sistema</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Stock Físico</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Discrepancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($datos['diferencias'] as $d)
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-500">{{ $d['fecha'] }}</td>
                            <td class="px-6 py-3 text-sm font-mono font-bold text-slate-900">{{ $d['codigo_lote'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-800">{{ $d['producto_nombre'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-550 text-right">{{ number_format($d['stock_sistema'], 2) }} {{ $d['unidad'] }}</td>
                            <td class="px-6 py-3 text-sm text-slate-750 text-right">{{ number_format($d['stock_physical'] ?? $d['stock_fisico'], 2) }} {{ $d['unidad'] }}</td>
                            <td class="px-6 py-3 text-sm font-bold text-right {{ $d['diferencia'] > 0 ? 'text-indigo-600' : 'text-rose-600' }}">
                                {{ $d['diferencia'] > 0 ? '+' : '' }}{{ number_format($d['diferencia'], 2) }} {{ $d['unidad'] }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-slate-400">No se encontraron discrepancias de auditoría en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
