<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diligencia de Tramitación - {{ $execution->title }}</title>
    <style>
        @page { size: A4; margin: 2.5cm 2cm 3.5cm 2cm; }
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; font-size: 12px; }

        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 12px; margin-bottom: 24px; }
        .logo-text { font-weight: 900; font-size: 20px; color: #0f172a; letter-spacing: -0.04em; }
        .logo-text .dot { color: #7c3aed; }
        .logo-text .suffix { color: #94a3b8; font-weight: 400; font-size: 13px; margin-left: 2px; }

        .doc-type { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #7c3aed; letter-spacing: 1.5px; }
        .doc-id { font-size: 9px; color: #64748b; font-family: monospace; }

        .main-title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0 0 12px 0; line-height: 1.25; }

        .info-grid { background: #f8fafc; padding: 10px 14px; border-radius: 6px; margin-bottom: 20px; border-left: 3px solid #7c3aed; }
        .info-label { font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 8px; letter-spacing: 0.5px; }
        .info-value { font-size: 11px; color: #1e293b; margin-bottom: 2px; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #7c3aed; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 20px; margin-bottom: 10px; letter-spacing: 1px; }

        .content { font-size: 12px; color: #334155; margin-bottom: 20px; }
        .content p { margin-bottom: 8px; }
        .content ul { margin-bottom: 8px; padding-left: 18px; }

        /* ── Tabla de Etapas del Circuito ── */
        .steps-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        .steps-table th { background: #f1f5f9; color: #475569; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 6px 8px; border: 1px solid #e2e8f0; text-align: left; }
        .steps-table td { padding: 6px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-returned { background: #fef3c7; color: #b45309; }
        .status-pending { background: #f1f5f9; color: #64748b; }

        /* ── Cajetines de firmas ── */
        .signatures-section { margin-top: 0; page-break-inside: avoid; }
        .signatures-grid { width: 100%; border-collapse: separate; border-spacing: 8px 10px; table-layout: fixed; }
        .signature-cell { width: 50%; vertical-align: top; }

        .sig-box {
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            height: 124px;
            min-height: 124px;
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

        .sig-inner-table { width: 100%; border-collapse: collapse; }
        .sig-inner-table td { vertical-align: top; padding: 0; }

        .sig-header { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .sig-header.signed { color: #059669; }
        .sig-header.pending { color: #94a3b8; }

        .sig-check { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #059669; }
        .sig-name { font-size: 10.5px; font-weight: bold; color: #0f172a; margin-bottom: 1px; }
        .sig-meta { font-size: 8px; color: #475569; margin-bottom: 2px; }
        .sig-date { font-size: 8px; color: #059669; font-weight: bold; margin-top: 2px; }
        .sig-hash { font-size: 7px; color: #475569; margin-top: 2px; line-height: 1.2; }
        .sig-hash code { font-family: monospace; font-size: 5.8px; color: #1e293b; word-break: break-all; }
        .sig-comment { font-size: 7.5px; font-style: italic; color: #334155; margin-top: 2px; padding-top: 2px; border-top: 1px dotted #a7f3d0; }

        .qr-cell { width: 62px; text-align: center; vertical-align: top; padding-left: 6px; padding-top: 2px; }
        .qr-img { border: 1px solid #cbd5e1; padding: 2px; background: #ffffff; border-radius: 3px; }
        .qr-label { font-size: 5.5px; color: #059669; font-weight: bold; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.5px; text-align: center; }

        .footer { position: fixed; bottom: -2.5cm; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>

    {{-- Encabezado Institucional --}}
    <div class="header">
        <table width="100%">
            <tr>
                <td style="text-align: left; vertical-align: middle;">
                    <div class="logo-text">sientia<span class="dot">.</span><span class="suffix">MTX</span></div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Sistema de Gestión y Tramitación Colegiada</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div class="doc-type">Diligencia Oficial de Tramitación</div>
                    <div class="doc-id">Exp. Circuito: #{{ $execution->id }}</div>
                    <div class="doc-id">Equipo: {{ $team->name }}</div>
                    @if($csv)
                        <div class="doc-id" style="color: #7c3aed; font-weight: bold;">CSV: {{ $csv }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <h1 class="main-title">{{ $execution->title }}</h1>

    {{-- Metadatos del Trámite --}}
    <div class="info-grid">
        <table width="100%">
            <tr>
                <td style="width:25%; vertical-align:top; padding-right:8px;">
                    <div class="info-label">Iniciado Por</div>
                    <div class="info-value">{{ $execution->initiator?->name ?? 'Usuario del Sistema' }}</div>
                </td>
                <td style="width:25%; vertical-align:top; padding:0 8px;">
                    <div class="info-label">Fecha Inicio</div>
                    <div class="info-value">{{ $execution->started_at?->format('d/m/Y H:i') ?? $execution->created_at->format('d/m/Y H:i') }}</div>
                </td>
                <td style="width:25%; vertical-align:top; padding:0 8px;">
                    <div class="info-label">Estado Actual</div>
                    <div class="info-value" style="font-weight:bold; text-transform:uppercase;">
                        {{ $execution->status_label }}
                    </div>
                </td>
                <td style="width:25%; vertical-align:top; padding-left:8px;">
                    <div class="info-label">Elemento Vinculado</div>
                    <div class="info-value">
                        @if($circuitable instanceof \App\Models\Activity)
                            Actividad ({{ $circuitable->type }}): {{ \Illuminate\Support\Str::limit($circuitable->title, 25) }}
                        @elseif($circuitable instanceof \App\Models\Expediente)
                            Expediente {{ $circuitable->code }}: {{ \Illuminate\Support\Str::limit($circuitable->title, 25) }}
                        @else
                            {{ class_basename($execution->circuitable_type) }} #{{ $execution->circuitable_id }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Cuerpo de la Propuesta / Memoria --}}
    @if($contentHtml)
        <div class="section-title">Contenido / Memoria de la Propuesta</div>
        <div class="content">
            {!! $contentHtml !!}
        </div>
    @endif

    {{-- Tabla Resumen de Etapas --}}
    <div class="section-title">Itinerario y Fases de la Tramitación</div>
    <table class="steps-table">
        <thead>
            <tr>
                <th style="width: 6%;">Nº</th>
                <th style="width: 28%;">Etapa</th>
                <th style="width: 22%;">Acción / Rol</th>
                <th style="width: 20%;">Asignado / Revisor</th>
                <th style="width: 14%;">Estado</th>
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
                            <div style="font-size: 8px; color: #64748b;">{{ $step->description }}</div>
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
                        @if($step->signed_at || (isset($signingStepId) && $step->id === $signingStepId))
                            <span style="color: #059669; font-weight: bold; font-size: 9px;"><span style="font-family: 'DejaVu Sans', sans-serif;">&#10003;</span> Firmado</span>
                        @elseif($step->requires_signature)
                            <span style="color: #d97706; font-size: 8px; font-weight: bold;">Requerida</span>
                        @else
                            <span style="color: #94a3b8; font-size: 8px;">No req.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Diligencia Oficial de Firmas Electrónicas y Conformidades en página dedicada --}}
    <div style="page-break-before: always; padding-top: 20px;">
        <div class="section-title" style="margin-top: 0;">Diligencia de Firmas Electrónicas y Conformidades</div>
        <p style="font-size: 11px; color: #64748b; margin-bottom: 15px;">
            A continuación se recogen las firmas y conformidades electrónicas reconocidas de los intervinientes en esta tramitación. El documento oficial consolidado incorpora firmas reconocidas con plena validez jurídica según la Ley 39/2015 y Reglamento eIDAS (UE 910/2014).
        </p>

        <div class="signatures-section">
            @php
                $stepChunks = $steps->chunk(2);
            @endphp

            @if($stepChunks->isEmpty())
                <div style="color: #94a3b8; font-style: italic; font-size: 10px;">
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
                                                $methodLabel = $isAutoFirma ? 'Firma Reconocida (AutoFirma PAdES)' : 'Conformidad Acreditada (CSV)';
                                                $fullHash = $step->signature_data['sha256_hash'] ?? hash('sha256', 'CIRCUIT:' . $execution->id . '|STEP:' . $step->id . '|ORDER:' . $step->order . '|USER:' . ($step->action_taken_by_id ?? 0) . '|TS:' . ($step->signed_at?->timestamp ?? 0) . '|CSV:' . ($csv ?? ''));
                                                $verifyUrl = route('public.circuits.verify', ['csv' => $csv ?? '']) . '?hash=' . $fullHash . '&step=' . $step->order;
                                                $qrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->errorCorrection('M')->margin(2)->generate($verifyUrl));
                                            @endphp
                                            <table class="sig-inner-table">
                                                <tr>
                                                    <td>
                                                        <div class="sig-header signed">
                                                            <span class="sig-check">&#10003;</span>
                                                            Etapa {{ $step->order }}: {{ $isAutoFirma ? 'Firma Reconocida' : 'Conformidad Acreditada' }}
                                                        </div>
                                                        <div class="sig-name">{{ $step->signature_data['signer_name'] ?? $step->actionUser?->name ?? 'Usuario Certificado' }}</div>
                                                        <div class="sig-meta">
                                                            {{ $step->name }} — {{ $step->signature_data['signer_email'] ?? $step->actionUser?->email ?? '' }}
                                                        </div>
                                                        <div class="sig-date">
                                                            {{ $isAutoFirma ? 'Firmado' : 'Acreditado' }} el {{ $step->signed_at->format('d/m/Y \a \l\a\s H:i:s') }}
                                                        </div>
                                                        <div class="sig-hash">
                                                            <div><strong>Método:</strong> {{ $methodLabel }}</div>
                                                            <div style="margin-top: 1px;">
                                                                <strong>Huella SHA-256:</strong><br>
                                                                <code>{{ strtolower($fullHash) }}</code>
                                                            </div>
                                                        </div>
                                                        @if($step->comments)
                                                            <div class="sig-comment">
                                                                "{{ \Illuminate\Support\Str::limit($step->comments, 75) }}"
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="qr-cell">
                                                        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="56" height="56" class="qr-img" alt="QR Verificación">
                                                        <div class="qr-label">Verificar QR</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        @elseif(isset($signingStepId) && $step->id === $signingStepId)
                                            @php
                                                $verifyUrl = route('public.circuits.verify', ['csv' => $csv ?? '']) . '?step=' . $step->order;
                                                $qrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->errorCorrection('M')->margin(2)->generate($verifyUrl));
                                            @endphp
                                            <table class="sig-inner-table">
                                                <tr>
                                                    <td>
                                                        <div class="sig-header signed">
                                                            <span class="sig-check">&#10003;</span>
                                                            Etapa {{ $step->order }}: Firma Reconocida
                                                        </div>
                                                        <div class="sig-name">{{ $signingUser?->name ?? $step->actionUser?->name ?? $step->assignedUser?->name ?? 'Usuario Firmante' }}</div>
                                                        <div class="sig-meta">
                                                            {{ $step->name }} — {{ $signingUser?->email ?? $step->actionUser?->email ?? $step->assignedUser?->email ?? '' }}
                                                        </div>
                                                        <!-- Espacio reservado para que la rúbrica visual estampada por AutoFirma encaje en el cajetín -->
                                                        <div style="height: 52px;"></div>
                                                    </td>
                                                    <td class="qr-cell">
                                                        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="56" height="56" class="qr-img" alt="QR Verificación">
                                                        <div class="qr-label">Cotejo CSV</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        @else
                                            <div class="sig-header pending">
                                                Etapa {{ $step->order }}: {{ $step->requires_signature ? 'Firma Electrónica' : 'Conformidad' }}
                                            </div>
                                            <div class="sig-name" style="color: #64748b;">
                                                {{ $step->assignedUser?->name ?? ($step->assignedRole ? 'Rol: ' . $step->assignedRole->name : 'Pendiente de asignación') }}
                                            </div>
                                            <div class="sig-meta">
                                                {{ $step->name }} ({{ $step->action_type_label }})
                                            </div>
                                            <!-- Espacio restante para que el sello de Autofirma encaje perfecto -->
                                            <div style="height: 52px;"></div>
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
        </div>
    </div>

    {{-- Pie de página legal con CSV --}}
    <div class="footer">
        Documento generado automáticamente por <strong>sientia.MTX</strong> el {{ now()->format('d/m/Y H:i:s') }}.
        @if($csv)
            Código Seguro de Verificación (CSV): <strong>{{ $csv }}</strong>.
        @endif
        Validez jurídica y administrativa según Ley 39/2015 y Reglamento eIDAS (UE 910/2014).
    </div>

</body>
</html>

