<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Circuitos de Tramitação e Aprovação',
    'singular' => 'Circuito de Tramitação',
    'circuit' => 'Circuito de Tramitação',
    'active_circuit' => 'Circuito Ativo',
    'template' => 'Modelo de Circuito',
    'templates' => 'Modelos de Circuitos',
    'circuit_template' => 'Modelo de Circuito de Tramitação',
    'no_circuit' => '(Sem circuito atribuído)',
    'idle' => 'Sem tramitação ativa',
    'auto_start' => 'Iniciar tramitação do circuito automaticamente ao guardar alterações',
    'auto_start_desc' => 'Associa o fluxo de assinaturas, justificações e vistos colegiados para este documento.',
    'execution' => 'Instância de Tramitação',
    'executions' => 'Instâncias de Tramitação',
    'pending_my_approval' => 'Pendentes do meu Visto / Assinatura',
    'start_circuit' => 'Iniciar Circuito de Tramitação',
    'public_board' => 'Quadro de Editais e Documentos Públicos',
    'official_status' => 'Status Oficial',
    'action_types' => [
        'endorsement' => 'Visto Prévio',
        'justification' => 'Justificação',
        'approval' => 'Aprovação',
        'publication' => 'Publicação',
    ],
    'statuses' => [
        'draft' => 'Rascunho',
        'in_progress' => 'Em tramitação',
        'approved' => 'Aprovado Definitivo',
        'rejected' => 'Rejeitado',
        'returned' => 'Devolvido para correção',
        'cancelled' => 'Cancelado',
        'published' => 'Publicado',
    ],
    'decisions' => [
        'approved' => 'Aprovar / De acordo',
        'rejected' => 'Rejeitar / Indeferir',
        'returned' => 'Devolver para correção',
    ],
    'assignee_types' => [
        'specific_user' => 'Utilizador específico',
        'team_role' => 'Papel de equipa',
        'activity_responsible' => 'Responsável atribuído',
        'creator' => 'Autor / Criador',
        'dynamic' => 'Dinâmico',
    ],
    'digital_signature' => 'Assinatura digital',
    'requires_signature' => 'Requer assinatura digital',
    'official_document' => 'Diligência / Documento oficial assinado (PDF)',
    'signed_electronically' => 'Assinado eletronicamente',
    'started_successfully' => 'Tramitação iniciada com sucesso.',
    'already_in_circuit' => 'Este item já possui um circuito de tramitação ativo em andamento.',
    'signature_required_error' => 'Esta etapa exige assinatura digital. Por favor, conclua o processo no assistente do AutoFirma.',
    'file_required_error' => 'Deve anexar o arquivo PDF assinado para concluir esta etapa.',
];
