<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuerdo - {{ $activity->title }}</title>
    <style>
        @page { size: A4; margin: 2.5cm 2cm 3.5cm 2cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; line-height: 1.6; margin: 0; padding: 0; font-size: 13px; }

        .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 30px; }
        .logo-text { font-weight: 900; font-size: 22px; color: #0f172a; letter-spacing: -0.04em; }
        .logo-text .dot { color: #8b5cf6; }
        .logo-text .suffix { color: #94a3b8; font-weight: 400; font-size: 14px; margin-left: 2px; }

        .doc-type { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; letter-spacing: 2px; }
        .doc-id { font-size: 9px; color: #94a3b8; font-family: monospace; }

        .main-title { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0 0 10px 0; line-height: 1.2; }

        .info-grid { background: #f8fafc; padding: 12px 15px; border-radius: 6px; margin-bottom: 25px; border-left: 3px solid #8b5cf6; }
        .info-label { font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 9px; }
        .info-value { font-size: 12px; color: #1e293b; margin-bottom: 2px; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #8b5cf6; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 25px; margin-bottom: 12px; letter-spacing: 1px; }

        .content { font-size: 13px; color: #334155; }
        .content p { margin-bottom: 12px; }
        .content ul { margin-bottom: 12px; padding-left: 18px; }

        /* ── Cajetines de firma ── */
        .signatures-section { margin-top: 40px; page-break-inside: avoid; }
        .signatures-grid { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .signature-cell { width: 50%; vertical-align: top; }

        .sig-box {
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            min-height: 90px;
            position: relative;
        }
        .sig-box.signed {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .sig-box.pending {
            border-color: #e2e8f0;
            background: #fafafa;
        }

        .sig-header { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .sig-header.signed { color: #059669; }
        .sig-header.pending { color: #94a3b8; }

        .sig-check { font-size: 14px; float: right; margin-top: -2px; }
        .sig-name { font-size: 11px; font-weight: bold; color: #1e293b; margin-bottom: 2px; }
        .sig-email { font-size: 9px; color: #64748b; font-family: monospace; margin-bottom: 6px; }
        .sig-role { font-size: 9px; color: #94a3b8; }
        .sig-date { font-size: 9px; color: #059669; margin-top: 6px; font-style: italic; }
        .sig-pending-line { border-top: 1px dashed #cbd5e1; margin-top: 18px; padding-top: 6px; font-size: 9px; color: #94a3b8; text-align: center; }

        .footer { position: fixed; bottom: -2.5cm; left: 0; right: 0; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td style="text-align: left; vertical-align: middle;">
                    <div class="logo-text">sientia<span class="dot">.</span><span class="suffix">MTX</span></div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div class="doc-type">Acuerdo de Adhesión / Resolución</div>
                    <div class="doc-id">Ref: {{ strtoupper($activity->uuid ?? $activity->id) }}</div>
                    <div class="doc-id">Equipo: {{ $team->name }}</div>
                </td>
            </tr>
        </table>
    </div>

    <h1 class="main-title">{{ $activity->title }}</h1>

    <div class="info-grid">
        <table width="100%">
            <tr>
                <td style="width:33%; vertical-align:top; padding:0 10px 0 0;">
                    <div class="info-label">Fecha</div>
                    <div class="info-value">{{ $activity->scheduled_date ? $activity->scheduled_date->format('d/m/Y') : now()->format('d/m/Y') }}</div>
                </td>
                <td style="width:33%; vertical-align:top; padding:0 10px;">
                    <div class="info-label">Estado</div>
                    <div class="info-value">{{ __('activities.statuses.' . $activity->status_value) }}</div>
                </td>
                <td style="width:33%; vertical-align:top; padding:0 0 0 10px;">
                    <div class="info-label">Promotor</div>
                    <div class="info-value">{{ $activity->creator->name ?? 'Sistema' }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($content))
    <div class="section-title">Términos del Acuerdo</div>
    <div class="content">
        {!! $content !!}
    </div>
    @endif

    @if(!empty($observations))
    <div class="section-title">Observaciones / Condiciones Adicionales</div>
    <div class="content">
        {!! $observations !!}
    </div>
    @endif


    <div style="page-break-before: always; padding-top: 20px;">
        <div class="section-title" style="margin-top: 0;">Panel de Firmas Electrónicas</div>
        <p style="font-size: 11px; color: #64748b; margin-bottom: 15px;">
            A continuación se muestran las rúbricas y constancias criptográficas de los intervinientes de este acuerdo. El documento original digital contiene las firmas PAdES válidas a todos los efectos legales.
        </p>

        <div class="signatures-section">
            @php
                $allSigners = [];
                // Miembros internos
                if (!empty($memberSignatures)) {
                    foreach ($memberSignatures as $ms) {
                        $allSigners[] = [
                            'name'      => $ms['name'] ?? 'Miembro del equipo',
                            'email'     => $ms['email'] ?? '',
                            'role'      => 'Miembro Interno',
                            'signed_at' => $ms['signed_at'] ?? null,
                        ];
                    }
                }
                // Invitados externos
                if (!empty($guests)) {
                    foreach ($guests as $g) {
                        $allSigners[] = [
                            'name'      => $g['name'] ?? 'Invitado Externo',
                            'email'     => $g['email'] ?? '',
                            'role'      => 'Asistente Externo',
                            'signed_at' => $g['signed_at'] ?? null,
                        ];
                    }
                }
                $chunks = array_chunk($allSigners, 2);
            @endphp

            @if(count($allSigners) > 0)
                <table class="signatures-grid">
                    @foreach($chunks as $row)
                        <tr>
                            @foreach($row as $signer)
                                <td class="signature-cell">
                                    <div class="sig-box {{ !empty($signer['signed_at']) ? 'signed' : 'pending' }}">
                                        @if(!empty($signer['signed_at']))
                                            <div class="sig-header signed">
                                                Firmado Electrónicamente
                                                <span class="sig-check">✓</span>
                                            </div>
                                            <div class="sig-name">{{ $signer['name'] }}</div>
                                            <div class="sig-email">{{ $signer['email'] }}</div>
                                            <div class="sig-role">{{ $signer['role'] }}</div>
                                            <div class="sig-date">Firmado el: {{ \Carbon\Carbon::parse($signer['signed_at'])->format('d/m/Y H:i:s') }}</div>
                                        @else
                                            <div class="sig-header pending">Firma Digital</div>
                                            <!-- Espacio restante para que el sello de Autofirma encaje perfecto -->
                                            <div style="height: 50px;"></div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            @if(count($row) == 1)
                                <td class="signature-cell"></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="font-size: 12px; color: #94a3b8; font-style: italic;">No hay firmantes asignados a este acuerdo.</p>
            @endif
        </div>
    </div>

    <div class="footer">
        Documento generado automáticamente por Sientia MTX &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Ref: {{ strtoupper($activity->uuid ?? $activity->id) }}
    </div>

</body>
</html>
