<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — SientiaMTX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ────────────────────────────────────────────────────── */
        body {
            font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.75;
            background: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Container para vista previa en pantalla ─────────────────── */
        .book-container {
            max-width: 820px;
            margin: 30px auto;
            background: #ffffff;
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
            border-radius: 12px;
            overflow: hidden;
            padding: 50px 65px;
        }

        /* ── Portada (Cover Page) ────────────────────────────────────── */
        .cover-page {
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            page-break-after: always;
            padding: 40px 20px;
            box-sizing: border-box;
        }
        .cover-logo {
            font-size: 38px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }
        .cover-logo span { color: #7c3aed; }
        .cover-org {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            margin-bottom: 40px;
        }
        .cover-divider {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #7c3aed, #4f46e5);
            border-radius: 2px;
            margin-bottom: 40px;
        }
        .cover-title {
            font-size: 36px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 16px;
            max-width: 600px;
        }
        .cover-subtitle {
            font-size: 15px;
            color: #475569;
            font-weight: 500;
            line-height: 1.5;
            margin-bottom: 35px;
            max-width: 520px;
        }
        .cover-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 6px 20px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
            margin-bottom: 60px;
        }
        .cover-footer {
            margin-top: auto;
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
        }

        /* ── Índice de Contenidos (TOC) ──────────────────────────────── */
        .toc-page {
            page-break-after: always;
            page-break-before: always;
            padding: 40px 0 30px;
        }
        .toc-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .toc-title {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .toc-count {
            font-size: 11px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc-item {
            display: flex;
            align-items: baseline;
            padding: 8px 0;
            border-bottom: 1px dotted #e2e8f0;
            font-size: 13px;
        }
        .toc-item-num {
            font-weight: 800;
            color: #7c3aed;
            width: 32px;
            shrink: 0;
        }
        .toc-item-title {
            font-weight: 700;
            color: #1e293b;
        }
        .toc-item-dots {
            flex: 1;
            border-bottom: 1px dotted #cbd5e1;
            margin: 0 10px;
            height: 1px;
            position: relative;
            top: -3px;
        }
        .toc-item-badge {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 0.05em;
        }

        /* ── Capítulos del Libro ─────────────────────────────────────── */
        .chapter-page {
            page-break-before: always;
            padding-top: 30px;
            margin-bottom: 40px;
        }
        .chapter-header {
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 14px;
            margin-bottom: 28px;
        }
        .chapter-label {
            font-size: 10px;
            font-weight: 900;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            display: block;
            margin-bottom: 4px;
        }
        .chapter-title {
            font-size: 26px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }
        .chapter-meta {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 4px;
        }

        /* ── Cuerpo del Documento (Markdown renderizado) ─────────────── */
        .doc-body {
            color: #334155;
            font-size: 13px;
            line-height: 1.7;
        }
        .doc-body h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 1.8em 0 0.5em;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 0.3em;
            line-height: 1.3;
        }
        .doc-body h2 {
            font-size: 15px;
            font-weight: 700;
            color: #7c3aed;
            margin: 1.5em 0 0.4em;
            line-height: 1.3;
        }
        .doc-body h3 {
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            margin: 1.2em 0 0.3em;
        }
        .doc-body p { margin: 0 0 1em; }
        .doc-body ul, .doc-body ol { margin: 0 0 1.2em 1.5em; padding: 0; }
        .doc-body li { margin-bottom: 0.3em; }
        .doc-body strong { font-weight: 700; color: #0f172a; }
        .doc-body code {
            font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
            background: #f1f5f9;
            color: #be185d;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .doc-body pre {
            background: #0f172a;
            border-radius: 8px;
            padding: 12px 16px;
            overflow-x: auto;
            margin: 1.2em 0;
        }
        .doc-body pre code {
            background: transparent;
            color: #e2e8f0;
            font-size: 11px;
            padding: 0;
        }
        .doc-body table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin: 1.2em 0;
            table-layout: auto;
        }
        .doc-body th, .doc-body td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: left;
            vertical-align: top;
        }
        .doc-body th {
            background: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
        }
        .doc-body blockquote {
            border-left: 3.5px solid #7c3aed;
            background: #f5f3ff;
            padding: 10px 14px;
            border-radius: 0 8px 8px 0;
            margin: 1.2em 0;
            color: #4c1d95;
            font-size: 12px;
        }
        .doc-body img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 1em 0;
        }

        /* ── Botón Flotante de Impresión ─────────────────────────────── */
        .print-fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            z-index: 9999;
        }
        .print-fab:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.6);
        }

        /* ── Reglas Específicas para Impresión (Paper / PDF) ──────────── */
        @media print {
            body { background: #fff !important; }
            .book-container {
                max-width: 100% !important;
                margin: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }
            .print-fab { display: none !important; }
            .chapter-page { page-break-before: always; }
            .cover-page { height: 100vh; page-break-after: always; }
            .toc-page { page-break-after: always; }
            .doc-body pre, .doc-body table, .doc-body blockquote { page-break-inside: avoid; }
            .doc-body h1, .doc-body h2, .doc-body h3 { page-break-after: avoid; }
            @page {
                size: A4;
                margin: 20mm 18mm 18mm 18mm;
            }
        }
    </style>
</head>
<body>

    <div class="book-container">

        {{-- 1. Portada Institucional del Libro --}}
        @if($includeCover)
            <div class="cover-page">
                <div class="cover-logo">sientia<span>.MTX</span></div>
                <div class="cover-org">Sistema de Gestión y Tramitación Colegiada</div>
                <div class="cover-divider"></div>

                <h1 class="cover-title">{{ $title }}</h1>
                <p class="cover-subtitle">{{ $subtitle }}</p>

                <div class="cover-badge">Compendio Oficial • {{ $version }}</div>

                <div class="cover-footer">
                    <div>Generado automáticamente el {{ now()->format('d/m/Y H:i') }}</div>
                    <div style="margin-top: 4px;">sientia.MTX Open Source Lab • Licencia GNU AGPL v3</div>
                </div>
            </div>
        @endif

        {{-- 2. Índice General de Capítulos (TOC) --}}
        @if($includeToc && count($chapters) > 1)
            <div class="toc-page">
                <div class="toc-header">
                    <h2 class="toc-title">Índice General</h2>
                    <span class="toc-count">{{ count($chapters) }} Capítulos Compilados</span>
                </div>

                <ul class="toc-list">
                    @foreach($chapters as $chap)
                        <li class="toc-item">
                            <span class="toc-item-num">{{ $chap['number'] }}.</span>
                            <span class="toc-item-title">{{ $chap['title'] }}</span>
                            <span class="toc-item-dots"></span>
                            <span class="toc-item-badge">{{ $chap['category'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 3. Capítulos Compilados --}}
        @foreach($chapters as $chap)
            <div class="chapter-page">
                <div class="chapter-header">
                    <span class="chapter-label">Capítulo {{ $chap['number'] }} &bull; {{ $chap['category'] }}</span>
                    <h2 class="chapter-title">{{ $chap['title'] }}</h2>
                    <div class="chapter-meta">Documentación Oficial de sientia.MTX &bull; Versión {{ $version }}</div>
                </div>

                <div class="doc-body">
                    {!! $chap['html'] !!}
                </div>
            </div>
        @endforeach

    </div>

    {{-- Botón Flotante para Lanzar Impresión / PDF --}}
    <button type="button" class="print-fab" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        <span>Imprimir / Guardar como PDF</span>
    </button>

</body>
</html>
