@extends('reportes.layout-pdf')

@section('title', 'Caracterización Demográfica Estudiantil')

@section('content')
    <div class="text-center font-bold text-indigo" style="font-size: 14px; margin-bottom: 20px; text-transform: uppercase;">
        Informe de Caracterización Demográfica
    </div>

    <!-- Metadata Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th>Año Lectivo</th>
                <th>Grado Académico</th>
                <th>Total Matriculados</th>
                <th>Masculino (M)</th>
                <th>Femenino (F)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $datos['anio'] }}</td>
                <td>{{ $datos['grado'] }}</td>
                <td class="font-bold text-indigo">{{ $datos['total_estudiantes'] }}</td>
                <td class="font-bold">{{ $datos['total_males'] }}</td>
                <td class="font-bold">{{ $datos['total_females'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Indicadores Demográficos Especiales</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Concepto / Indicador</th>
                <th class="text-center">Total Estudiantes</th>
                <th class="text-center">Porcentaje (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Estudiantes con Necesidades de Apoyo Educativo (DAI)</td>
                <td class="text-center font-bold">{{ $datos['total_dai'] }}</td>
                <td class="text-center">{{ $datos['total_estudiantes'] > 0 ? round(($datos['total_dai'] / $datos['total_estudiantes']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td>Estudiantes en Condición de Extraedad</td>
                <td class="text-center font-bold">{{ $datos['total_extraedad'] }}</td>
                <td class="text-center">{{ $datos['total_estudiantes'] > 0 ? round(($datos['total_extraedad'] / $datos['total_estudiantes']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td>Estudiantes en Condición de Repitencia</td>
                <td class="text-center font-bold">{{ $datos['total_repitentes'] }}</td>
                <td class="text-center">{{ $datos['total_estudiantes'] > 0 ? round(($datos['total_repitentes'] / $datos['total_estudiantes']) * 100, 1) : 0 }}%</td>
            </tr>
        </tbody>
    </table>

    <div style="width: 100%; margin-top: 15px;" class="clearfix">
        <!-- Económica -->
        <div style="float: left; width: 48%;">
            <div class="section-title">Actividad Económica Estudiante</div>
            <table class="report-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th class="text-center">Cant.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos['actividades_economicas'] as $act => $count)
                        <tr>
                            <td>{{ $act ?: 'No Especificada' }}</td>
                            <td class="text-center font-bold">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Convivencia -->
        <div style="float: right; width: 48%;">
            <div class="section-title">Estructura / Convivencia Familiar</div>
            <table class="report-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>Convivencia con</th>
                        <th class="text-center">Cant.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos['convivencias'] as $conv => $count)
                        <tr>
                            <td>{{ $conv ?: 'No Especificada' }}</td>
                            <td class="text-center font-bold">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="width: 100%; margin-top: 15px;" class="clearfix">
        <!-- Académico Tutor -->
        <div style="float: left; width: 48%;">
            <div class="section-title">Nivel Académico de los Tutores</div>
            <table class="report-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>Nivel Educativo</th>
                        <th class="text-center">Cant.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos['tutores_academicos'] as $nivel => $count)
                        <tr>
                            <td>{{ $nivel ?: 'No Especificada' }}</td>
                            <td class="text-center font-bold">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Situación Laboral Tutor -->
        <div style="float: right; width: 48%;">
            <div class="section-title">Situación Laboral de los Tutores</div>
            <table class="report-table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>Estado Laboral</th>
                        <th class="text-center">Cant.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos['tutores_laborales'] as $sit => $count)
                        <tr>
                            <td>{{ $sit ?: 'No Especificada' }}</td>
                            <td class="text-center font-bold">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sello y firma -->
    <div style="margin-top: 60px; width: 100%; text-align: center;">
        <div style="border-top: 1px solid #94a3b8; width: 250px; margin: 0 auto; padding-top: 5px; font-size: 10px;">
            Sello y Firma de Dirección Escolar
        </div>
    </div>
@endsection
