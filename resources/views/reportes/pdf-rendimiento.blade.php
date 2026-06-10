@extends('reportes.layout-pdf')

@section('title', 'Informe de Rendimiento Académico')

@section('content')
    <div class="text-center font-bold text-indigo" style="font-size: 14px; margin-bottom: 20px; text-transform: uppercase;">
        Informe de Rendimiento Académico
    </div>

    <!-- Metadata Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th>Año Lectivo</th>
                <th>Grado y Sección</th>
                <th>Período</th>
                <th>Estado del Informe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $informe->anoLectivo->anio }}</td>
                <td>{{ $informe->seccion->nombre_completo }}</td>
                <td>{{ $informe->trimestre ? "Trimestre {$informe->trimestre}" : 'Consolidado Anual' }}</td>
                <td><span class="badge badge-success">Cerrado / Oficial</span></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Estadísticas de Matrícula y Alumnos</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-center">Total</th>
                <th class="text-center">Hombres (M)</th>
                <th class="text-center">Mujeres (F)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Matrícula Inicial</td>
                <td class="text-center font-bold">{{ $informe->matricula_inicial }}</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td class="font-bold">Matrícula Actual (Activos)</td>
                <td class="text-center font-bold">{{ $informe->matricula_actual }}</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td>Alumnos Aprobados (Promedio &ge; 5.0)</td>
                <td class="text-center font-bold text-emerald">{{ $informe->aprobados_m + $informe->aprobados_f }}</td>
                <td class="text-center text-emerald">{{ $informe->aprobados_m }}</td>
                <td class="text-center text-emerald">{{ $informe->aprobados_f }}</td>
            </tr>
            <tr>
                <td>Alumnos Reprobados (Promedio < 5.0)</td>
                <td class="text-center font-bold text-rose">{{ $informe->reprobados_m + $informe->reprobados_f }}</td>
                <td class="text-center text-rose">{{ $informe->reprobados_m }}</td>
                <td class="text-center text-rose">{{ $informe->reprobados_f }}</td>
            </tr>
            <tr>
                <td>Alumnos Desertores / Retirados</td>
                <td class="text-center font-bold text-rose">{{ $informe->desertores }}</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Indicadores Especiales</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Indicador</th>
                <th class="text-center">Cantidad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Estudiantes en Extraedad</td>
                <td class="text-center font-bold">{{ $informe->sobredad }}</td>
                <td>Alumnos cuya edad supera la edad teórica correspondiente al grado escolar.</td>
            </tr>
            <tr>
                <td class="font-bold">Estudiantes Repitentes</td>
                <td class="text-center font-bold">{{ $informe->repitentes }}</td>
                <td>Alumnos que cursan el grado por segunda vez o más.</td>
            </tr>
        </tbody>
    </table>

    @if($informe->observaciones)
        <div class="section-title">Observaciones de la Dirección</div>
        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background-color: #fafafa; font-size: 10px; line-height: 1.6; font-style: italic;">
            "{{ $informe->observaciones }}"
        </div>
    @endif

    <!-- Signature block -->
    <div style="margin-top: 60px; width: 100%;" class="clearfix">
        <div style="float: left; width: 45%; text-align: center;">
            <div style="border-top: 1px solid #94a3b8; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">
                Firma del Docente
            </div>
        </div>
        <div style="float: right; width: 45%; text-align: center;">
            <div style="border-top: 1px solid #94a3b8; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">
                Sello y Firma de Dirección
            </div>
        </div>
    </div>
@endsection
