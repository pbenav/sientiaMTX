<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

return [
    'title' => 'Routing & Approval Circuits',
    'singular' => 'Approval Circuit',
    'circuit' => 'Approval Circuit',
    'active_circuit' => 'Active Circuit',
    'template' => 'Circuit Template',
    'templates' => 'Circuit Templates',
    'circuit_template' => 'Approval Circuit Template',
    'no_circuit' => '(No circuit assigned)',
    'idle' => 'No Active Routing',
    'auto_start' => 'Start circuit processing automatically upon saving',
    'auto_start_desc' => 'Associates the multi-step approval, endorsement, and signature workflow for this document.',
    'execution' => 'Routing Instance',
    'executions' => 'Routing Instances',
    'pending_my_approval' => 'Pending my Review / Signature',
    'start_circuit' => 'Start Approval Circuit',
    'public_board' => 'Notice Board & Public Documents',
    'official_status' => 'Official Status',
    'action_types' => [
        'endorsement' => 'Endorsement',
        'justification' => 'Justification',
        'approval' => 'Approval',
        'publication' => 'Publication',
    ],
    'statuses' => [
        'draft' => 'Draft',
        'in_progress' => 'In progress',
        'approved' => 'Approved (Final)',
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
    'digital_signature' => 'Digital Signature',
    'requires_signature' => 'Requires Digital Signature',
    'official_document' => 'Official Signed Document (PDF)',
    'signed_electronically' => 'Electronically Signed',
    'started_successfully' => 'Routing started successfully.',
    'already_in_circuit' => 'This item already has an active approval circuit in progress.',
    'signature_required_error' => 'This step requires a digital signature. Please complete the process using the AutoFirma Assistant.',
    'file_required_error' => 'You must attach the signed PDF file to complete this step.',
];
