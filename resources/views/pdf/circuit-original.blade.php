<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $execution->title }}</title>
    <style>
        @page { size: A4; margin: 2.2cm 1.8cm 2.2cm 3.2cm; }
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; font-size: 11.5px; }

        /* ── Banda Lateral Oficial (Sede Electrónica / CSV / Cotejo) ── */
        .lateral-band {
            position: fixed;
            top: -1.2cm;
            left: -2.8cm;
            bottom: -1.2cm;
            width: 2.5cm;
            border-right: 1px solid #cbd5e1;
            text-align: center;
        }
        .lateral-qr-box {
            position: absolute;
            bottom: 15px;
            left: 6px;
            width: 60px;
            text-align: center;
        }
        .lateral-qr-img {
            width: 54px;
            height: 54px;
        }
        .lateral-qr-label {
            font-size: 5.5px;
            font-family: 'DejaVu Sans Mono', monospace;
            color: #64748b;
            margin-top: 2px;
            font-weight: bold;
            text-transform: uppercase;
        }

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
            padding: 6px 10px;
            min-height: 75px;
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

    {{-- Banda Lateral Oficial de Sede Electrónica (Fija en todas las páginas) --}}
    <div class="lateral-band">
        @if(!empty($qrSvg))
            <div class="lateral-qr-box">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" class="lateral-qr-img" alt="QR Cotejo">
                <div class="lateral-qr-label">Cotejo CSV</div>
            </div>
        @endif
    </div>

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
            $fontBold = $fontMetrics->getFont("DejaVu Sans", "bold");
            $fontRegular = $fontMetrics->getFont("DejaVu Sans", "normal");

            // Pista 1: Cabecera Sede Electrónica y CSV (color morado oficial Sientia #7c3aed)
            $track1 = "SEDE ELECTRÓNICA SIENTIA.MTX  |  CÓDIGO SEGURO DE VERIFICACIÓN (CSV): {{ $csv }}";
            $pdf->page_text(73, 560, $track1, $fontBold, 6.8, [0.49, 0.23, 0.93], 0, 0, 90);

            // Pista 2: Diligencia de Firmas registradas
            $track2 = "FIRMANTES: {{ addslashes($signersText ?? 'En proceso de tramitación') }}  |  Sello PAdES oficial verificado";
            $pdf->page_text(61, 710, $track2, $fontRegular, 6.2, [0.2, 0.25, 0.3], 0, 0, 90);

            // Pista 3: Validez legal y cotejo en sede
            $track3 = "Documento electrónico auténtico (Ley 39/2015 y eIDAS)  |  Cotejo: {{ $verifyUrl ?? route('public.circuits.verify', $csv) }}";
            $pdf->page_text(49, 680, $track3, $fontRegular, 5.8, [0.4, 0.45, 0.5], 0, 0, 90);

            // Paginación en la banda lateral
            $pagText = "Pág. {PAGE_NUM} de {PAGE_COUNT}";
            $pdf->page_text(73, 90, $pagText, $fontBold, 6.5, [0.4, 0.45, 0.5], 0, 0, 90);
        }
    </script>
</body>
</html>
