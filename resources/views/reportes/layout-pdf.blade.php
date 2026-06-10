<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reporte SIG')</title>
    <style>
        @page {
            margin: 120px 40px 60px 40px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        header {
            position: fixed;
            top: -95px;
            left: 0px;
            right: 0px;
            height: 85px;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 5px;
        }
        .header-logo {
            float: left;
            width: 70px;
            height: 70px;
            text-align: center;
            background-color: #6366f1;
            color: white;
            font-weight: bold;
            font-size: 24px;
            line-height: 70px;
            border-radius: 10px;
            margin-right: 15px;
        }
        .header-text {
            float: left;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }
        .header-subtitle {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .header-mined {
            font-size: 10px;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .header-meta {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #64748b;
            margin-top: 15px;
        }
        footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 8px;
            color: #94a3b8;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
            text-align: right;
        }
        .page-number:after {
            content: counter(page);
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        /* Tables styling */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        table.report-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 2px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
        }
        table.report-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }
        table.report-table tr:nth-child(even) td {
            background-color: #f8fafc/50;
        }
        /* Helper classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-rose { color: #f43f5e; }
        .text-emerald { color: #10b981; }
        .text-indigo { color: #6366f1; }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }
        .badge-warning { background-color: #fef9c3; color: #a16207; }
    </style>
</head>
<body>
    <header class="clearfix">
        <div class="header-logo">SIG</div>
        <div class="header-text">
            <p class="header-mined">Ministerio de Educación, Ciencia y Tecnología</p>
            <h1 class="header-title">C.E. Cantón Pablo J. Aguirre</h1>
            <p class="header-subtitle">Sistema de Información Gerencial (SIG) — Reportes Oficiales</p>
        </div>
        <div class="header-meta">
            Fecha de impresión: {{ now()->format('d/m/Y h:i A') }}<br>
            Generado por: {{ auth()->user()->name }}
        </div>
    </header>

    <footer>
        <div class="clearfix">
            <div class="footer-left">
                © {{ now()->format('Y') }} Centro Escolar Cantón Pablo J. Aguirre. Todos los derechos reservados.
            </div>
            <div class="footer-right">
                Pág. <span class="page-number"></span>
            </div>
        </div>
    </footer>

    <main>
        @yield('content')
    </main>
</body>
</html>
