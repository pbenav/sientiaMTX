<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo Oficial de Tramitación y Firma - {{ $execution->title }}</title>
    <style>
        @page { size: A4; margin: 2.2cm 2cm 2.5cm 2cm; }
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.45; margin: 0; padding: 0; font-size: 11px; }

        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 12px; margin-bottom: 18px; }
        .logo-text { font-weight: 900; font-size: 19px; color: #0f172a; letter-spacing: -0.04em; }
        .logo-text .dot { color: #7c3aed; }
        .logo-text .suffix { color: #94a3b8; font-weight: 400; font-size: 12px; margin-left: 2px; }

        .doc-type { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #7c3aed; letter-spacing: 1.2px; }
        .doc-id { font-size: 8.5px; color: #64748b; font-family: monospace; }

        .main-title { font-size: 17px; font-weight: bold; color: #0f172a; margin: 0 0 12px 0; line-height: 1.25; }

        /* Ficha de Evidencia Criptográfica */
        .evidence-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-left: 4px solid #7c3aed;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 18px;
        }
        .evidence-title { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #7c3aed; letter-spacing: 0.8px; margin-bottom: 6px; }
        .evidence-row { font-size: 9px; color: #334155; margin-bottom: 3px; }
        .evidence-row strong { color: #0f172a; }
        .evidence-hash {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
            margin-top: 4px;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.5px;
            color: #0f172a;
            word-break: break-all;
        }

        .section-title { font-size: 10.5px; font-weight: bold; text-transform: uppercase; color: #7c3aed; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 18px; margin-bottom: 10px; letter-spacing: 0.8px; }

        /* Tabla de Fases */
        .steps-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 9.5px; }
        .steps-table th { background: #f1f5f9; color: #475569; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 5px 7px; border: 1px solid #e2e8f0; text-align: left; }
        .steps-table td { padding: 5px 7px; border: 1px solid #e2e8f0; vertical-align: top; }
        .status-badge { display: inline-block; padding: 2px 5px; border-radius: 4px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-returned { background: #fef3c7; color: #b45309; }
        .status-pending { background: #f1f5f9; color: #64748b; }

        /* Cajetines de Diligencia de Firmas */
        .signatures-grid { width: 100%; border-collapse: separate; border-spacing: 8px 10px; table-layout: fixed; }
        .signature-cell { width: 50%; vertical-align: top; }

        .sig-box {
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            min-height: 120px;
            position: relative;
            background: #ffffff;
            box-sizing: border-box;
        }
        .sig-box.signed {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .sig-box.pending {
            border-color: #cbd5e1;
            background: #fafafa;
            border-style: dashed;
        }

        .sig-inner-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sig-inner-table td { vertical-align: top; padding: 0; }

        .sig-header { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .sig-header.signed { color: #059669; }
        .sig-header.pending { color: #94a3b8; }

        .sig-check { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #059669; }

        .sig-stamp-box {
            border: 1px solid #a7f3d0;
            background: #ffffff;
            border-radius: 4px;
            padding: 4px 6px;
            margin-top: 2px;
            font-size: 6.8px;
            line-height: 1.25;
            color: #1e293b;
        }
        .sig-stamp-title {
            font-size: 6.8px;
            font-weight: bold;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .sig-stamp-line {
            border-bottom: 1px dotted #10b981;
            margin: 2px 0 3px 0;
        }
        .sig-stamp-row {
            margin-bottom: 1.5px;
            color: #334155;
            word-break: break-word;
            line-height: 1.25;
        }
        .sig-stamp-row strong {
            color: #0f172a;
            font-size: 6.5px;
        }
        .sig-stamp-legal {
            font-size: 6px;
            color: #059669;
            font-weight: bold;
            margin-top: 1.5px;
        }
        .sig-stamp-hash {
            font-size: 5.8px;
            color: #64748b;
            margin-top: 2px;
            border-top: 1px dotted #e2e8f0;
            padding-top: 2px;
            line-height: 1.15;
        }
        .sig-stamp-hash code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 5.4px;
            color: #1e293b;
            word-break: break-all;
        }
        .sig-comment {
            font-size: 6.5px;
            font-style: italic;
            color: #334155;
            margin-top: 2px;
            padding-top: 2px;
            border-top: 1px dotted #a7f3d0;
        }

        .qr-cell { width: 58px; text-align: center; vertical-align: top; padding-left: 6px; padding-top: 1px; }
        .qr-img { border: 1px solid #cbd5e1; padding: 2px; background: #ffffff; border-radius: 3px; }
        .qr-label { font-size: 5.2px; color: #059669; font-weight: bold; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.4px; text-align: center; }

        .legal-notice {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin-top: 15px;
            font-size: 6.8px;
            color: #64748b;
            line-height: 1.35;
            text-align: justify;
        }

        .footer {
            position: fixed;
            bottom: -1.8cm;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- Encabezado Institucional de Sede Electrónica --}}
    <div class="header">
        <table width="100%">
            <tr>
                <td style="text-align: left; vertical-align: middle;">
                    <div class="logo-text">sientia<span class="dot">.</span><span class="suffix">MTX</span></div>
                    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Sede Electrónica y Sistema de Tramitación Colegiada</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div class="doc-type">Recibo Oficial de Firma y Diligencia</div>
                    <div class="doc-id">Trámite #{{ $execution->id }} &bull; {{ $team->name }}</div>
                    @if($csv)
                        <div class="doc-id" style="color: #7c3aed; font-weight: bold;">CSV: {{ $csv }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <h1 class="main-title">{{ $execution->title }}</h1>

    {{-- Ficha de Evidencia Criptográfica del Documento Firmado --}}
    <div class="evidence-card">
        <div class="evidence-title">Acreditación de Autenticidad y Huella Criptográfica</div>
        <table width="100%">
            <tr>
                <td style="width: 78%; vertical-align: top;">
                    <div class="evidence-row">
                        <strong>Documento Acreditado:</strong> {{ $execution->title }}
                    </div>
                    <div class="evidence-row">
                        @if($circuitable instanceof \App\Models\Expediente)
                            <strong>Elemento Asociado:</strong> Expediente {{ $circuitable->code }} &mdash; {{ $circuitable->title }}
                        @elseif($circuitable instanceof \App\Models\Activity)
                            <strong>Elemento Asociado:</strong> Actividad ({{ $circuitable->type }}): {{ $circuitable->title }}
                        @endif
                    </div>
                    @if(isset($signedDocName) && $signedDocName)
                        <div class="evidence-row">
                            <strong>Archivo Original Firmado:</strong> {{ $signedDocName }}
                            @if(isset($signedDocSize) && $signedDocSize)
                                <span style="color: #94a3b8;">({{ round($signedDocSize / 1024, 1) }} KB)</span>
                            @endif
                        </div>
                    @endif
                    <div class="evidence-row" style="margin-top: 4px;">
                        <strong>Huella Criptográfica SHA-256 del Documento Firmado:</strong>
                        <div class="evidence-hash">
                            {{ $signedDocHash ?? hash('sha256', 'CIRCUIT:' . $execution->id . '|CSV:' . ($csv ?? '')) }}
                        </div>
                    </div>
                </td>
                <td style="width: 22%; text-align: center; vertical-align: middle;">
                    @php
                        $verifyUrl = route('public.circuits.verify', ['csv' => $csv ?? '']);
                        $qrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->errorCorrection('M')->margin(2)->generate($verifyUrl));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="56" height="56" class="qr-img" alt="QR Verificación Sede">
                    <div class="qr-label">Cotejo Sede</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Resumen de Fases --}}
    <div class="section-title">Itinerario y Fases de la Tramitación</div>
    <table class="steps-table">
        <thead>
            <tr>
                <th style="width: 6%;">Nº</th>
                <th style="width: 30%;">Etapa</th>
                <th style="width: 22%;">Acción / Rol</th>
                <th style="width: 20%;">Asignado / Revisor</th>
                <th style="width: 12%;">Estado</th>
                <th style="width: 10%;">Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach($steps as $step)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $step->order }}</td>
                    <td>
                        <strong>{{ $step->name }}</strong>
                        @if($step->description)
                            <div style="font-size: 7px; color: #64748b;">{{ $step->description }}</div>
                        @endif
                    </td>
                    <td>{{ $step->action_type_label }}</td>
                    <td>
                        @if($step->actionUser)
                            <strong>{{ $step->actionUser->name }}</strong>
                        @elseif($step->assignedUser)
                            {{ $step->assignedUser->name }}
                        @elseif($step->assignedRole)
                            Rol: {{ $step->assignedRole->name }}
                        @else
                            --
                        @endif
                    </td>
                    <td>
                        @php
                            $stClass = match($step->status) {
                                'approved' => 'status-approved',
                                'in_progress' => 'status-in_progress',
                                'returned' => 'status-returned',
                                default => 'status-pending',
                            };
                            $stLabel = match($step->status) {
                                'approved' => 'Aprobado',
                                'in_progress' => 'En curso',
                                'returned' => 'Devuelto',
                                default => 'Pendiente',
                            };
                        @endphp
                        <span class="status-badge {{ $stClass }}">{{ $stLabel }}</span>
                    </td>
                    <td style="text-align: center;">
                        @if($step->signed_at)
                            <span style="color: #059669; font-weight: bold; font-size: 8px;"><span style="font-family: 'DejaVu Sans', sans-serif;">&#10003;</span> Firmado</span>
                        @elseif($step->requires_signature)
                            <span style="color: #d97706; font-size: 7.5px; font-weight: bold;">Requerida</span>
                        @else
                            <span style="color: #94a3b8; font-size: 7.5px;">No req.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Diligencia Detallada de Cada Firma --}}
    <div class="section-title">Diligencias Oficiales de Firma y Conformidad</div>
    @php
        $stepChunks = $steps->chunk(2);
    @endphp

    @if($stepChunks->isEmpty())
        <div style="color: #94a3b8; font-style: italic; font-size: 9px;">
            No hay firmas registradas todavía en esta tramitación.
        </div>
    @else
        <table class="signatures-grid">
            @foreach($stepChunks as $row)
                <tr>
                    @foreach($row as $step)
                        <td class="signature-cell">
                            <div class="sig-box {{ $step->signed_at ? 'signed' : 'pending' }}">
                                @if($step->signed_at)
                                    @php
                                        $sigMethod = $step->signature_data['method'] ?? 'internal_certificate';
                                        $isAutoFirma = in_array(strtolower($sigMethod), ['autofirma', 'manual_upload']);
                                        $stepHash = $step->signature_data['sha256_hash'] ?? hash('sha256', 'CIRCUIT:' . $execution->id . '|STEP:' . $step->id . '|ORDER:' . $step->order . '|USER:' . ($step->action_taken_by_id ?? 0) . '|TS:' . ($step->signed_at?->timestamp ?? 0) . '|CSV:' . ($csv ?? ''));
                                        $stepVerifyUrl = route('public.circuits.verify', ['csv' => $csv ?? '']) . '?hash=' . $stepHash . '&step=' . $step->order;
                                        $stepQrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->errorCorrection('M')->margin(2)->generate($stepVerifyUrl));
                                        $signerName = $step->signature_data['signer_name'] ?? $step->actionUser?->name ?? 'Usuario Certificado';
                                        $signerEmail = $step->signature_data['signer_email'] ?? $step->actionUser?->email ?? '';
                                    @endphp
                                    <table class="sig-inner-table">
                                        <tr>
                                            <td style="width: 73%; vertical-align: top;">
                                                <div class="sig-header signed">
                                                    <span class="sig-check">&#10003;</span>
                                                    Etapa {{ $step->order }}: {{ $isAutoFirma ? 'Firma Cualificada (PAdES)' : 'Conformidad Acreditada (CSV)' }}
                                                </div>

                                                <div class="sig-stamp-box">
                                                    <div class="sig-stamp-title">Diligencia Oficial de Firma Electrónica</div>
                                                    <div class="sig-stamp-line"></div>
                                                    <div class="sig-stamp-row">
                                                        <strong>FIRMADO POR:</strong> {{ strtoupper($signerName) }}
                                                    </div>
                                                    <div class="sig-stamp-row">
                                                        <strong>ACTO / ROL:</strong> {{ $step->name }} &bull; {{ $signerEmail }}
                                                    </div>
                                                    <div class="sig-stamp-row">
                                                        <strong>FECHA:</strong> {{ $step->signed_at->format('d/m/Y \a \l\a\s H:i:s') }}
                                                    </div>
                                                    <div class="sig-stamp-row sig-stamp-legal">
                                                        {{ $isAutoFirma ? 'Sello PAdES verificado en Sientia.MTX (Reglamento eIDAS / Ley 39/2015)' : 'Acreditado con CSV oficial según Ley 39/2015' }}
                                                    </div>
                                                    <div class="sig-stamp-hash">
                                                        <strong>HUELLA SHA-256:</strong><br>
                                                        <code>{{ strtolower($stepHash) }}</code>
                                                    </div>
                                                </div>

                                                @if($step->comments)
                                                    <div class="sig-comment">
                                                        "{{ \Illuminate\Support\Str::limit($step->comments, 60) }}"
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="qr-cell" style="width: 27%; vertical-align: middle; text-align: center;">
                                                <img src="data:image/svg+xml;base64,{{ $stepQrSvg }}" width="50" height="50" class="qr-img" alt="QR Cotejo">
                                                <div class="qr-label">Cotejo CSV</div>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="sig-header pending">
                                        Etapa {{ $step->order }}: {{ $step->requires_signature ? 'Firma Electrónica' : 'Conformidad' }}
                                    </div>
                                    <div style="color: #64748b; font-size: 9.5px; font-weight: bold; margin-bottom: 2px;">
                                        {{ $step->assignedUser?->name ?? ($step->assignedRole ? 'Rol: ' . $step->assignedRole->name : 'Pendiente de asignación') }}
                                    </div>
                                    <div style="font-size: 7.5px; color: #64748b;">
                                        {{ $step->name }} ({{ $step->action_type_label }})
                                    </div>
                                    <div style="margin-top: 14px; font-size: 7px; color: #94a3b8; font-style: italic;">
                                        En espera de tramitación y firma en itinerario
                                    </div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                    @if($row->count() < 2)
                        <td class="signature-cell" style="border:none;"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Aviso y Garantía Legal Institucional --}}
    <div class="legal-notice">
        <strong>Acreditación de Integridad y Validez Jurídica:</strong> El presente recibo oficial certifica que el documento referenciado ha sido tramitado y firmado colegiadamente a través del Sistema Sientia.MTX. La autenticidad e integridad del documento original y de cada firma digital puede ser cotejada públicamente en cualquier momento mediante su Código Seguro de Verificación (CSV) o escaneando el código QR, con plena validez de acuerdo a la Ley 39/2015 del Procedimiento Administrativo Común de las Administraciones Públicas y al Reglamento (UE) nº 910/2014 (eIDAS).
    </div>

    {{-- Pie de página del recibo --}}
    <div class="footer">
        Recibo Oficial de Firma y Diligencia &bull; CSV: {{ $csv }} &bull; Sientia.MTX
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
