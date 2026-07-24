<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — SientiaMTX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ────────────────────────────────────────────────────── */
        body {
            font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif;
            color: #1e293b;
            font-size: 13.5px;
            line-height: 1.75;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Wrapper ─────────────────────────────────────────────────── */
        .page {
            max-width: 780px;
            margin: 0 auto;
            padding: 36px 52px 48px;
        }

        /* ── Cabecera corporativa (estilo Ficha Técnica) ─────────────── */
        .corp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
            margin-bottom: 2px;
            border-bottom: 2.5px solid #0f172a;
        }
        .corp-logo {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.04em;
            line-height: 1;
        }
        .corp-logo em { color: #7c3aed; font-style: normal; }
        .corp-meta { text-align: right; }
        .corp-type {
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #7c3aed;
        }
        .corp-section {
            font-size: 8px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 2px;
        }

        /* ── Bloque título ───────────────────────────────────────────── */
        .doc-title {
            font-size: 30px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin: 18px 0 5px;
        }
        .doc-meta {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 20px;
        }
        .doc-divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 0 0 24px;
        }

        /* ── Cuerpo ──────────────────────────────────────────────────── */
        .doc-body { color: #334155; }

        .doc-body h1 {
            font-size: 20px; font-weight: 800; color: #0f172a;
            margin: 1.8em 0 0.4em;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 0.3em; line-height: 1.3;
        }
        .doc-body h2 {
            font-size: 16px; font-weight: 700; color: #7c3aed;
            margin: 1.5em 0 0.4em; line-height: 1.3;
        }
        .doc-body h3 {
            font-size: 14px; font-weight: 700; color: #475569;
            margin: 1.3em 0 0.3em;
        }
        .doc-body h4, .doc-body h5, .doc-body h6 {
            font-size: 13px; font-weight: 700; color: #64748b;
            margin: 1em 0 0.3em;
        }
        .doc-body p  { margin: 0 0 0.9em; }
        .doc-body ul,
        .doc-body ol { margin: 0 0 1em 1.5em; padding: 0; }
        .doc-body li { margin-bottom: 0.3em; }
        .doc-body strong { font-weight: 700; color: #0f172a; }
        .doc-body em  { font-style: italic; }
        .doc-body a   { color: #7c3aed; text-decoration: none; }

        .doc-body code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background: #f1f5f9; color: #be185d;
            padding: 1px 5px; border-radius: 4px;
            font-size: 11.5px; font-weight: 600;
        }
        .doc-body pre {
            background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 13px 16px;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 11px; line-height: 1.6;
            overflow-x: hidden; white-space: pre-wrap; word-break: break-all;
            margin: 0.8em 0 1.1em;
        }
        .doc-body pre code {
            background: none; color: #334155;
            padding: 0; font-weight: normal; border-radius: 0;
        }
        .doc-body blockquote {
            border-left: 3px solid #7c3aed; background: #f5f3ff;
            margin: 1em 0; padding: 9px 16px;
            border-radius: 0 6px 6px 0; color: #4c1d95; font-style: normal;
        }
        .doc-body table {
            width: 100%; border-collapse: collapse;
            margin: 1em 0 1.4em; font-size: 12px;
        }
        .doc-body th {
            text-align: left; padding: 7px 10px;
            border-bottom: 2px solid #cbd5e1;
            color: #475569; font-weight: 700; background: #f8fafc;
        }
        .doc-body td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0; vertical-align: top;
        }
        .doc-body tr:last-child td { border-bottom: none; }
        .doc-body img {
            max-width: 100%; height: auto;
            border-radius: 6px; margin: 0.8em 0;
        }
        .doc-body hr {
            border: none; border-top: 1px solid #e2e8f0; margin: 1.5em 0;
        }

        /* ── Pie ─────────────────────────────────────────────────────── */
        .doc-footer {
            margin-top: 40px; padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            display: flex; justify-content: space-between;
            font-size: 8px; font-weight: 700;
            color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.08em;
        }

        /* ── Botón de impresión (solo pantalla, desaparece al imprimir) ─ */
        .print-btn {
            position: fixed; bottom: 24px; right: 24px;
            background: #7c3aed; color: #fff;
            border: none; border-radius: 12px;
            padding: 12px 22px; font-size: 14px; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 20px rgba(124,58,237,0.4);
            font-family: inherit;
            display: flex; align-items: center; gap: 8px;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #6d28d9; }

        /* ── Print media ─────────────────────────────────────────────── */
        @media print {
            .print-btn { display: none !important; }
            body { font-size: 12.5px; }
            .page { padding: 0; max-width: 100%; }
            .doc-body h2, .doc-body h3 { page-break-after: avoid; }
            pre, table, blockquote { page-break-inside: avoid; }
            @page { size: A4; margin: 20mm 22mm 18mm 22mm; }
        }
    </style>
</head>
<body>
    <div class="page">

        {{-- Cabecera corporativa — visible solo si ?header=1 (por defecto sí) --}}
        @if(request('header', '1') !== '0')
        <div class="corp-header">
            <div class="corp-logo">sientia<em>MTX</em></div>
            <div class="corp-meta">
                <div class="corp-type">Documentación Oficial</div>
                <div class="corp-section">Centro de Documentación</div>
            </div>
        </div>
        @endif

        {{-- Título y fecha --}}
        <h1 class="doc-title">{{ $title }}</h1>
        <div class="doc-meta">Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</div>
        <hr class="doc-divider">

        {{-- Contenido Markdown renderizado --}}
        <div class="doc-body">
            {!! $content !!}
        </div>

        {{-- Pie --}}
        <footer class="doc-footer">
            <span>Sientia MTX Ecosystem &bull; Centro de Documentación</span>
            <span>{{ now()->format('d/m/Y H:i') }}</span>
        </footer>

    </div>

    {{-- Botón flotante visible solo en pantalla --}}
    <button class="print-btn" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Imprimir / Guardar PDF
    </button>

    <script>
        // Auto-lanzar impresión al cargar la página
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</body>
</html>
