<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Routing & Approval Circuits',
    'singular' => 'Approval Circuit',
    'template' => 'Circuit Template',
    'templates' => 'Circuit Templates',
    'execution' => 'Routing Instance',
    'executions' => 'Routing Instances',
    'pending_my_approval' => 'Pending my Review / Signature',
    'start_circuit' => 'Start Approval Circuit',
    'public_board' => 'Notice Board & Public Documents',
    'action_types' => [
        'endorsement' => 'Endorsement',
        'justification' => 'Justification',
        'approval' => 'Approval',
        'publication' => 'Publication',
    ],
    'statuses' => [
        'draft' => 'Draft',
        'in_progress' => 'In progress',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'returned' => 'Returned for amendment',
        'cancelled' => 'Cancelled',
        'published' => 'Published',
    ],
    'decisions' => [
        'approved' => 'Approve / Endorse',
        'rejected' => 'Reject / Deny',
        'returned' => 'Return for amendment',
    ],
    'assignee_types' => [
        'specific_user' => 'Specific user',
        'team_role' => 'Team role',
        'activity_responsible' => 'Assigned responsible',
        'creator' => 'Author / Creator',
        'dynamic' => 'Dynamic',
    ],
];
