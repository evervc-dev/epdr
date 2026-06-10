@extends('reportes.layout-pdf')

@section('title', 'Reporte de Inventario de Alimentos')

@section('content')
    <div class="text-center font-bold text-indigo" style="font-size: 14px; margin-bottom: 20px; text-transform: uppercase;">
        Reporte de Inventario de Alimentos
    </div>

    <!-- Metadata Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th>Rango de Fechas</th>
                <th>Producto Filtrado</th>
                <th>Fecha de Generación</th>
                <th>Estado General</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $datos['fecha_desde'] }} al {{ $datos['fecha_hasta'] }}</td>
                <td>{{ $datos['producto_nombre'] }}</td>
                <td>{{ now()->format('d/m/Y h:i A') }}</td>
                <td><span class="badge badge-info">Oficial de Bodega</span></td>
            </tr>
        </tbody>
    </table>

    <!-- 1. Estado de lotes -->
    <div class="section-title">1. Estado y Stock Actual de Lotes</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Código Lote</th>
                <th>Producto</th>
                <th>Ingreso</th>
                <th class="text-right">Peso Recibido</th>
                <th class="text-right">Stock Actual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['lotes'] as $l)
                <tr>
                    <td class="font-mono font-bold">{{ $l['codigo_lote'] }}</td>
                    <td>{{ $l['producto_nombre'] }}</td>
                    <td>{{ $l['fecha_ingreso'] }}</td>
                    <td class="text-right">{{ number_format($l['peso_total_kg'], 2) }} {{ $l['unidad'] }}</td>
                    <td class="text-right font-bold text-indigo">{{ number_format($l['stock_actual'], 2) }} {{ $l['unidad'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-slate-400">No hay lotes registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- 2. Movimientos en el período -->
    <div class="section-title">2. Movimientos de Bodega en el Período</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto / Lote</th>
                <th>Tipo</th>
                <th class="text-right">Cantidad</th>
                <th>Registrado Por</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['movimientos'] as $m)
                <tr>
                    <td>{{ $m['fecha'] }}</td>
                    <td>
                        <div class="font-bold">{{ $m['producto_nombre'] }}</div>
                        <div class="font-mono text-slate-500" style="font-size: 8px;">Lote: {{ $m['codigo_lote'] }}</div>
                    </td>
                    <td>
                        @if($m['tipo'] === 'entrada')
                            <span class="badge badge-success">Entrada</span>
                        @elseif($m['tipo'] === 'salida')
                            <span class="badge badge-danger">Salida</span>
                        @else
                            <span class="badge badge-warning">Merma</span>
                        @endif
                    </td>
                    <td class="text-right font-bold {{ $m['tipo'] === 'entrada' ? 'text-emerald' : 'text-rose' }}">
                        {{ $m['tipo'] === 'entrada' ? '+' : '-' }}{{ number_format($m['cantidad'], 2) }} {{ $m['unidad'] }}
                    </td>
                    <td>{{ $m['usuario'] }}</td>
                    <td style="max-width: 150px; font-size: 8px;">{{ $m['observaciones'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-slate-400">No hay movimientos registrados en este rango de fechas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- 3. Diferencias de auditoría -->
    <div class="section-title">3. Discrepancias / Ajustes de Auditorías Recientes</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Fecha Auditoría</th>
                <th>Código Lote</th>
                <th>Producto</th>
                <th class="text-right">Stock Sistema</th>
                <th class="text-right">Stock Físico</th>
                <th class="text-right">Discrepancia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['diferencias'] as $d)
                <tr>
                    <td>{{ $d['fecha'] }}</td>
                    <td class="font-mono font-bold">{{ $d['codigo_lote'] }}</td>
                    <td>{{ $d['producto_nombre'] }}</td>
                    <td class="text-right">{{ number_format($d['stock_sistema'], 2) }} {{ $d['unidad'] }}</td>
                    <td class="text-right">{{ number_format($d['stock_fisico'], 2) }} {{ $d['unidad'] }}</td>
                    <td class="text-right font-bold {{ $d['diferencia'] > 0 ? 'text-indigo' : 'text-rose' }}">
                        {{ $d['diferencia'] > 0 ? '+' : '' }}{{ number_format($d['diferencia'], 2) }} {{ $d['unidad'] }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-slate-400">No se detectaron discrepancias en auditorías recientes.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Sello y firmas -->
    <div style="margin-top: 60px; width: 100%;" class="clearfix">
        <div style="float: left; width: 45%; text-align: center;">
            <div style="border-top: 1px solid #94a3b8; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">
                Encargado de Bodega
            </div>
        </div>
        <div style="float: right; width: 45%; text-align: center;">
            <div style="border-top: 1px solid #94a3b8; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">
                Sello y Firma de Dirección
            </div>
        </div>
    </div>
@endsection
