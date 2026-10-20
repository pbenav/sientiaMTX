<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Genehmigungs- und Umlaufkreise',
    'singular' => 'Umlaufkreis',
    'circuit' => 'Umlaufkreis',
    'active_circuit' => 'Aktiver Umlaufkreis',
    'template' => 'Umlaufkreis-Vorlage',
    'templates' => 'Umlaufkreis-Vorlagen',
    'circuit_template' => 'Vorlage für Genehmigungskreis',
    'no_circuit' => '(Kein Umlaufkreis zugewiesen)',
    'idle' => 'Kein aktiver Umlauf',
    'auto_start' => 'Umlaufkreis nach dem Speichern automatisch starten',
    'auto_start_desc' => 'Verknüpft den Freigabe- und Genehmigungsworkflow für dieses Dokument.',
    'execution' => 'Bearbeitungsvorgang',
    'executions' => 'Bearbeitungsvorgänge',
    'pending_my_approval' => 'Ausstehend für meine Prüfung / Unterschrift',
    'start_circuit' => 'Genehmigungskreis starten',
    'public_board' => 'Amtliche Bekanntmachungstafel & Öffentliche Dokumente',
    'official_status' => 'Offizieller Status',
    'action_types' => [
        'endorsement' => 'Sichtung / Freigabe',
        'justification' => 'Begründung',
        'approval' => 'Genehmigung',
        'publication' => 'Veröffentlichung',
    ],
    'statuses' => [
        'draft' => 'Entwurf',
        'in_progress' => 'In Bearbeitung',
        'approved' => 'Endgültig genehmigt',
        'rejected' => 'Abgelehnt',
        'returned' => 'Zur Nachbesserung zurückgegeben',
        'cancelled' => 'Abgebrochen',
        'published' => 'Veröffentlicht',
    ],
    'decisions' => [
        'approved' => 'Genehmigen / Befürworten',
        'rejected' => 'Ablehnen',
        'returned' => 'Zur Überarbeitung zurücksenden',
    ],
    'assignee_types' => [
        'specific_user' => 'Bestimmter Benutzer',
        'team_role' => 'Teamrolle',
        'activity_responsible' => 'Zugewiesener Verantwortlicher',
        'creator' => 'Ersteller',
        'dynamic' => 'Dynamisch',
    ],
];
