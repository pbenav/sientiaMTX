<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Circuits de Validation et Signature',
    'singular' => 'Circuit de Validation',
    'circuit' => 'Circuit de Validation',
    'active_circuit' => 'Circuit Actif',
    'template' => 'Modèle de Circuit',
    'templates' => 'Modèles de Circuits',
    'circuit_template' => 'Modèle de Circuit de Validation',
    'no_circuit' => '(Aucun circuit assigné)',
    'idle' => 'Aucun circuit actif',
    'auto_start' => 'Démarrer automatiquement le circuit lors de l’enregistrement',
    'auto_start_desc' => 'Associe le flux collégial de visas, justifications et approbations pour ce document.',
    'execution' => 'Instance de Traitement',
    'executions' => 'Instances de Traitement',
    'pending_my_approval' => 'En attente de mon Visa / Signature',
    'start_circuit' => 'Démarrer le Circuit de Validation',
    'public_board' => 'Tableau d’Affichage et Documents Publics',
    'official_status' => 'Statut officiel',
    'action_types' => [
        'endorsement' => 'Visa préalable',
        'justification' => 'Justification',
        'approval' => 'Approbation',
        'publication' => 'Publication',
    ],
    'statuses' => [
        'draft' => 'Brouillon',
        'in_progress' => 'En cours de traitement',
        'approved' => 'Approuvé définitif',
        'rejected' => 'Rejeté',
        'returned' => 'Retourné pour correction',
        'cancelled' => 'Annulé',
        'published' => 'Publié',
    ],
    'decisions' => [
        'approved' => 'Approuver / Conforme',
        'rejected' => 'Rejeter / Refuser',
        'returned' => 'Renvoyer pour correction',
    ],
    'assignee_types' => [
        'specific_user' => 'Utilisateur spécifique',
        'team_role' => 'Rôle d’équipe',
        'activity_responsible' => 'Responsable assigné',
        'creator' => 'Auteur / Créateur',
        'dynamic' => 'Dynamique',
    ],
];
