<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $execution->title }}</title>
    <style>
        @page { size: A4; margin: 2.2cm 2cm 2.5cm 2cm; }
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; font-size: 11.5px; }

        .doc-header { border-bottom: 2px solid #7c3aed; padding-bottom: 12px; margin-bottom: 20px; }
        .doc-title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0 0 6px 0; line-height: 1.25; }
        .doc-meta { font-size: 9px; color: #64748b; margin-top: 4px; }
        .doc-meta strong { color: #334155; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #7c3aed; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 22px; margin-bottom: 12px; letter-spacing: 1px; }

        .content { font-size: 11px; color: #334155; margin-bottom: 20px; line-height: 1.6; }
        .content p { margin-bottom: 8px; }
        .content ul { margin-bottom: 8px; padding-left: 20px; }
        .content ol { margin-bottom: 8px; padding-left: 20px; }
        .content li { margin-bottom: 3px; }

        .content h1 { font-size: 14.5px; font-weight: bold; color: #0f172a; margin: 16px 0 8px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .content h2 { font-size: 13px; font-weight: bold; color: #0f172a; margin: 14px 0 6px 0; }
        .content h3 { font-size: 11.5px; font-weight: bold; color: #1e293b; margin: 12px 0 4px 0; }
        .content h4, .content h5, .content h6 { font-size: 10.5px; font-weight: bold; color: #334155; margin: 10px 0 4px 0; }

        .content hr { border: 0; border-top: 1px solid #e2e8f0; margin: 14px 0; }
        .content blockquote { border-left: 3px solid #7c3aed; background: #f8fafc; padding: 6px 12px; margin: 8px 0; color: #475569; font-style: italic; }

        /* Soporte Unicode completo para código y diagramas ASCII / Box Drawing */
        .content pre, pre {
            font-family: 'DejaVu Sans Mono', monospace;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            margin: 10px 0;
            font-size: 7.8px;
            line-height: 1.35;
            color: #1e293b;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .content code, code {
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .content p code, .content li code {
            background: #f1f5f9;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 9px;
            color: #7c3aed;
            border: 1px solid #e2e8f0;
        }

        /* Tablas Markdown */
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9.5px;
        }
        .content table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .content table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .content table tr:nth-child(even) {
            background: #f8fafc;
        }

        /* ── Espacio reservado para rúbrica de AutoFirma ── */
        .autofirma-box {
            margin-top: 20px;
            border: 1.5px dashed #cbd5e1;
            background: #fafafa;
            border-radius: 6px;
            padding: 4px 8px;
            min-height: 52px;
            page-break-inside: avoid;
            position: relative;
        }
        .autofirma-anchor {
            font-size: 1pt;
            color: transparent;
            line-height: 1;
            display: inline-block;
        }
    </style>
</head>
<body>

    {{-- Encabezado Limpio del Documento --}}
    <div class="doc-header">
        <h1 class="doc-title">{{ $execution->title }}</h1>
        <div class="doc-meta">
            @if($circuitable instanceof \App\Models\Expediente)
                <strong>Expediente:</strong> {{ $circuitable->code }} &bull; {{ $circuitable->title }} &nbsp;|&nbsp;
            @elseif($circuitable instanceof \App\Models\Activity)
                <strong>Actividad:</strong> {{ $circuitable->title }} &nbsp;|&nbsp;
            @endif
            <strong>Promotor:</strong> {{ $execution->initiator?->name ?? 'Usuario del Sistema' }} &nbsp;|&nbsp;
            <strong>Fecha:</strong> {{ $execution->created_at->format('d/m/Y') }}
        </div>
    </div>

    {{-- Contenido Sustantivo del Documento --}}
    @if($contentHtml)
        <div class="content">
            {!! $contentHtml !!}
        </div>
    @endif

    {{-- Espacio para rúbrica visible de AutoFirma / Certificado Digital --}}
    <div class="autofirma-box">
        <span class="autofirma-anchor">[[AUTOFIRMA_RUBRICA_ANCHOR]]</span>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 7.5;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size, [0.58, 0.64, 0.72]);
        }
    </script>
</body>
</html>
