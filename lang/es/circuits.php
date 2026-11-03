<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Circuitos de Tramitación',
    'singular' => 'Circuito de Tramitación',
    'circuit' => 'Circuito de Tramitación',
    'active_circuit' => 'Circuito Activo',
    'template' => 'Plantilla de Circuito',
    'templates' => 'Plantillas de Circuitos',
    'circuit_template' => 'Plantilla de Circuito de Tramitación',
    'no_circuit' => '(Sin circuito asignado)',
    'idle' => 'Sin Tramitación Activa',
    'auto_start' => 'Iniciar tramitación del circuito automáticamente al guardar cambios',
    'auto_start_desc' => 'Asocia el flujo de firmas, justificaciones y vistos buenos colegiados que debe seguir este documento.',
    'execution' => 'Tramitación',
    'executions' => 'Tramitaciones',
    'pending_my_approval' => 'Pendientes de mi Visto Bueno / Firma',
    'start_circuit' => 'Iniciar Circuito de Tramitación',
    'public_board' => 'Tablón Edictal y Documentos Públicos',
    'official_status' => 'Estado Oficial',
    'action_types' => [
        'endorsement' => 'Visto Bueno',
        'justification' => 'Justificación',
        'approval' => 'Aprobación',
        'publication' => 'Publicación',
    ],
    'statuses' => [
        'draft' => 'Borrador',
        'in_progress' => 'En tramitación',
        'approved' => 'Aprobado Definitivo',
        'rejected' => 'Rechazado',
        'returned' => 'Devuelto para subsanación',
        'cancelled' => 'Cancelado',
        'published' => 'Publicado',
    ],
    'decisions' => [
        'approved' => 'Aprobar / Conforme',
        'rejected' => 'Rechazar / Denegar',
        'returned' => 'Devolver para subsanar',
    ],
    'assignee_types' => [
        'specific_user' => 'Usuario específico',
        'team_role' => 'Rol de equipo',
        'activity_responsible' => 'Responsable asignado',
        'creator' => 'Autor / Creador',
        'dynamic' => 'A designar en tramitación',
    ],
    'digital_signature' => 'Firma Digital',
    'requires_signature' => 'Requiere Firma Digital',
    'official_document' => 'Diligencia / Documento Oficial Firmado (PDF)',
    'signed_electronically' => 'Firmado Electrónicamente',
    'started_successfully' => 'Tramitación iniciada correctamente.',
    'already_in_circuit' => 'Este elemento ya tiene una tramitación activa en curso.',
    'signature_required_error' => 'Esta etapa exige firma digital. Por favor, completa el proceso en el Asistente de AutoFirma.',
    'file_required_error' => 'Debes adjuntar el archivo PDF firmado para completar esta etapa.',
];
