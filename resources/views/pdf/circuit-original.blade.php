<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $execution->title }}</title>
    <style>
        @page { size: A4; margin: 2cm 2cm 3.5cm 2cm; }
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; font-size: 11px; }

        .doc-header { border-bottom: 2px solid #7c3aed; padding-bottom: 10px; margin-bottom: 18px; }
        .doc-title { font-size: 19px; font-weight: bold; color: #0f172a; margin: 0 0 5px 0; line-height: 1.25; }
        .doc-meta { font-size: 9px; color: #64748b; margin-top: 3px; }
        .doc-meta strong { color: #334155; }

        .content { font-size: 11px; color: #334155; margin-bottom: 15px; line-height: 1.6; }
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

        /* ── CAJETÍN OFICIAL AL PIE DE PÁGINA (Fijo en todas las páginas) ── */
        .footer-cajetin {
            position: fixed;
            bottom: -3.1cm;
            left: 0;
            right: 0;
            height: 2.7cm;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            padding: 6px 8px;
            box-sizing: border-box;
        }

        .cajetin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cajetin-table td {
            vertical-align: middle;
            padding: 0 5px;
        }

        .col-sede {
            width: 32%;
            border-right: 1px solid #e2e8f0;
        }

        .col-firmas {
            width: 53%;
            border-right: 1px solid #e2e8f0;
            padding-left: 8px !important;
        }

        .col-qr {
            width: 15%;
            text-align: center;
        }

        .sede-title {
            font-size: 8px;
            font-weight: bold;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .sede-csv {
            font-size: 7.5px;
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .sede-url {
            font-size: 6.8px;
            color: #64748b;
            word-break: break-all;
        }

        .legal-notice {
            font-size: 6px;
            color: #94a3b8;
            margin-top: 2px;
            font-style: italic;
        }

        .firmas-title {
            font-size: 7.8px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .firma-item {
            font-size: 7px;
            color: #334155;
            line-height: 1.35;
            margin-bottom: 2px;
        }

        .firma-item strong {
            color: #047857;
        }

        .firma-item-pending {
            font-size: 7px;
            color: #94a3b8;
            font-style: italic;
        }

        .firma-check {
            color: #059669;
            font-weight: bold;
            font-size: 8px;
        }

        .qr-img {
            width: 50px;
            height: 50px;
        }

        .qr-caption {
            font-size: 5.5px;
            color: #64748b;
            font-weight: bold;
            margin-top: 1px;
            text-transform: uppercase;
            font-family: 'DejaVu Sans Mono', monospace;
        }
    </style>
</head>
<body>

    {{-- Cajetín Oficial de Firma al Pie de Página (Fijo en todas las páginas) --}}
    <div class="footer-cajetin">
        <table class="cajetin-table">
            <tr>
                <td class="col-sede">
                    <div class="sede-title">Sede Electrónica Sientia.MTX</div>
                    <div class="sede-csv">CSV: {{ $csv }}</div>
                    <div class="sede-url">Cotejo: {{ $verifyUrl }}</div>
                    <div class="legal-notice">Validez legal según Ley 39/2015 del PACAP y Reg. eIDAS (UE 910/2014)</div>
                </td>
                <td class="col-firmas">
                    <div class="firmas-title">Diligencia de Firmas Electrónicas Colegiadas</div>
                    @forelse($signedSteps as $st)
                        @php
                            $sName = $st->actionUser?->name ?? $st->assignedUser?->name ?? 'Firmante';
                            $sDate = $st->signed_at ? $st->signed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
                            $sRole = $st->action_type_label ?? $st->name;
                            $sCert = $st->signature_data['certificate_issuer'] ?? ($st->signature_data['method'] === 'autofirma' ? 'AutoFirma / FNMT-DNIe' : 'Certificado Digital');
                        @endphp
                        <div class="firma-item">
                            <span class="firma-check">&#10003;</span>
                            <strong>{{ $sName }}</strong> ({{ $sRole }}) &bull; {{ $sDate }} &bull; {{ $sCert }}
                        </div>
                    @empty
                        <div class="firma-item-pending">
                            En espera de resolución y firma en el itinerario
                        </div>
                    @endforelse
                    <div class="legal-notice">Documento electrónico con firmas criptográficas PAdES reconocidas</div>
                </td>
                <td class="col-qr">
                    @if(!empty($qrSvg))
                        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" class="qr-img" alt="QR">
                        <div class="qr-caption">Cotejo CSV</div>
                    @endif
                </td>
            </tr>
        </table>
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

    {{-- Paginación en esquina inferior derecha --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 7.0;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - $width - 56;
            $y = $pdf->get_height() - 14;
            $pdf->page_text($x, $y, $text, $font, $size, [0.58, 0.64, 0.72]);
        }
    </script>
</body>
</html>
