<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Circuitos de Tramitación',
    'singular' => 'Circuito de Tramitación',
    'template' => 'Plantilla de Circuito',
    'templates' => 'Plantillas de Circuitos',
    'execution' => 'Tramitación',
    'executions' => 'Tramitaciones',
    'pending_my_approval' => 'Pendientes de mi Visto Bueno / Firma',
    'start_circuit' => 'Iniciar Circuito de Tramitación',
    'public_board' => 'Tablón Edictal y Documentos Públicos',
    'action_types' => [
        'endorsement' => 'Visto Bueno',
        'justification' => 'Justificación',
        'approval' => 'Aprobación',
        'publication' => 'Publicación',
    ],
    'statuses' => [
        'draft' => 'Borrador',
        'in_progress' => 'En tramitación',
        'approved' => 'Aprobado',
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
];
