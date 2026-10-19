<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\Expediente;
use App\Models\CircuitTemplate;
use App\Models\CircuitTemplateStep;
use App\Models\CircuitExecution;
use App\Models\CircuitExecutionStep;
use App\Models\CircuitPublication;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CircuitWorkflowTest extends TestCase
{
    public function test_circuit_template_model_fillable_and_relations(): void
    {
        $template = new CircuitTemplate([
            'team_id' => 1,
            'created_by_id' => 10,
            'name' => 'Propuesta y Aprobación',
            'description' => 'Circuito estándar para validar propuestas',
            'target_type' => 'both',
            'is_active' => true,
            'settings' => ['auto_publish' => true],
        ]);

        $this->assertEquals(1, $template->team_id);
        $this->assertEquals(10, $template->created_by_id);
        $this->assertEquals('Propuesta y Aprobación', $template->name);
        $this->assertEquals('both', $template->target_type);
        $this->assertTrue($template->is_active);
        $this->assertEquals(['auto_publish' => true], $template->settings);

        $this->assertInstanceOf(BelongsTo::class, $template->team());
        $this->assertInstanceOf(BelongsTo::class, $template->creator());
        $this->assertInstanceOf(HasMany::class, $template->steps());
        $this->assertInstanceOf(HasMany::class, $template->executions());
    }

    public function test_circuit_template_step_action_labels_and_relations(): void
    {
        $step1 = new CircuitTemplateStep([
            'circuit_template_id' => 1,
            'order' => 1,
            'name' => 'Visto Bueno Técnico',
            'action_type' => CircuitTemplateStep::ACTION_ENDORSEMENT,
            'assignee_type' => CircuitTemplateStep::ASSIGNEE_SPECIFIC_USER,
            'assignee_id' => 20,
        ]);

        $step2 = new CircuitTemplateStep([
            'circuit_template_id' => 1,
            'order' => 2,
            'name' => 'Justificación Presupuestaria',
            'action_type' => CircuitTemplateStep::ACTION_JUSTIFICATION,
            'assignee_type' => CircuitTemplateStep::ASSIGNEE_RESPONSIBLE,
        ]);

        $step3 = new CircuitTemplateStep([
            'circuit_template_id' => 1,
            'order' => 3,
            'name' => 'Aprobación de Dirección',
            'action_type' => CircuitTemplateStep::ACTION_APPROVAL,
            'assignee_type' => CircuitTemplateStep::ASSIGNEE_TEAM_ROLE,
            'assignee_id' => 5,
        ]);

        $step4 = new CircuitTemplateStep([
            'circuit_template_id' => 1,
            'order' => 4,
            'name' => 'Publicación en Tablón Edictal',
            'action_type' => CircuitTemplateStep::ACTION_PUBLICATION,
        ]);

        $this->assertEquals('Visto Bueno', $step1->action_type_label);
        $this->assertEquals('Justificación', $step2->action_type_label);
        $this->assertEquals('Aprobación', $step3->action_type_label);
        $this->assertEquals('Publicación', $step4->action_type_label);

        $this->assertInstanceOf(BelongsTo::class, $step1->template());
        $this->assertInstanceOf(BelongsTo::class, $step1->assignedUser());
        $this->assertInstanceOf(BelongsTo::class, $step3->assignedRole());
    }

    public function test_circuit_execution_attributes_progress_and_relations(): void
    {
        $execution = new CircuitExecution([
            'team_id' => 1,
            'circuitable_type' => Activity::class,
            'circuitable_id' => 100,
            'title' => 'Tramitación de Actividad #100',
            'initiated_by_id' => 10,
            'status' => CircuitExecution::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->assertInstanceOf(MorphTo::class, $execution->circuitable());
        $this->assertInstanceOf(BelongsTo::class, $execution->team());
        $this->assertInstanceOf(BelongsTo::class, $execution->template());
        $this->assertInstanceOf(BelongsTo::class, $execution->initiator());
        $this->assertInstanceOf(BelongsTo::class, $execution->currentStep());
        $this->assertInstanceOf(HasMany::class, $execution->steps());
        $this->assertInstanceOf(HasOne::class, $execution->publication());

        $this->assertTrue($execution->is_active);

        // Progreso con colección simulada de pasos
        $stepA = new CircuitExecutionStep(['order' => 1, 'status' => CircuitExecutionStep::STATUS_APPROVED]);
        $stepB = new CircuitExecutionStep(['order' => 2, 'status' => CircuitExecutionStep::STATUS_PENDING]);
        $execution->setRelation('steps', collect([$stepA, $stepB]));

        $this->assertEquals(50, $execution->progress_percentage);

        // Completar todos los pasos
        $stepB->status = CircuitExecutionStep::STATUS_APPROVED;
        $this->assertEquals(100, $execution->progress_percentage);
    }

    public function test_circuit_execution_step_authorization_and_action(): void
    {
        $team = new Team(['name' => 'Equipo Test']);
        $team->id = 1;
        $team->owner_id = 99; // Propietario

        $userDirect = new User(['name' => 'Usuario Asignado']);
        $userDirect->id = 25;

        $userOther = new User(['name' => 'Otro Usuario']);
        $userOther->id = 30;

        $owner = new User(['name' => 'Dueño']);
        $owner->id = 99;

        $execution = new CircuitExecution(['team_id' => 1]);
        $execution->setRelation('team', $team);

        $step = new CircuitExecutionStep([
            'order' => 1,
            'action_type' => CircuitTemplateStep::ACTION_ENDORSEMENT,
            'assigned_to_user_id' => 25,
            'status' => CircuitExecutionStep::STATUS_PENDING,
        ]);
        $step->setRelation('execution', $execution);

        // El usuario asignado directamente puede actuar
        $this->assertTrue($step->canUserAct($userDirect));

        // Otro usuario cualquiera NO puede actuar
        $this->assertFalse($step->canUserAct($userOther));

        // El propietario del equipo siempre tiene facultades de actuación/supervisión
        $this->assertTrue($step->canUserAct($owner));

        // Los administradores de la plataforma siempre tienen facultades de supervisión y firma
        $admin = new User(['name' => 'Admin']);
        $admin->is_admin = true;
        $this->assertTrue($step->canUserAct($admin));
    }

    public function test_circuit_publication_attributes_and_active_window(): void
    {
        $pub = new CircuitPublication([
            'team_id' => 1,
            'circuit_execution_id' => 50,
            'published_by_id' => 10,
            'title' => 'Bando Municipal 2026',
            'document_code' => 'PUB-2026-0001',
            'status' => CircuitPublication::STATUS_PUBLISHED,
            'publication_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addDays(15)->toDateString(),
        ]);

        $this->assertInstanceOf(BelongsTo::class, $pub->team());
        $this->assertInstanceOf(BelongsTo::class, $pub->execution());
        $this->assertInstanceOf(BelongsTo::class, $pub->publisher());

        // La publicación dentro de su ventana de vigencia es activa
        $this->assertTrue($pub->is_currently_active);

        // Si ya expiró, ya no es activa
        $pub->expiration_date = now()->subDays(2)->toDateString();
        $this->assertFalse($pub->is_currently_active);

        // Si fue retirada, tampoco es activa
        $pub->status = CircuitPublication::STATUS_WITHDRAWN;
        $this->assertFalse($pub->is_currently_active);
    }

    public function test_circuit_digital_signature_attributes_and_accessors(): void
    {
        $tmplStep = new CircuitTemplateStep([
            'name' => 'Firma de Intervención',
            'action_type' => CircuitTemplateStep::ACTION_APPROVAL,
            'requires_signature' => true,
        ]);
        $this->assertTrue($tmplStep->requires_signature);

        $execStep1 = new CircuitExecutionStep([
            'order' => 1,
            'name' => 'Visto Bueno',
            'requires_signature' => false,
            'status' => CircuitExecutionStep::STATUS_APPROVED,
        ]);

        $execStep2 = new CircuitExecutionStep([
            'order' => 2,
            'name' => 'Firma y Aprobación',
            'requires_signature' => true,
            'status' => CircuitExecutionStep::STATUS_APPROVED,
            'signed_at' => now(),
            'signature_data' => [
                'method' => 'internal_certificate',
                'signer_name' => 'Directora General',
                'signer_email' => 'directora@sientia.com',
            ],
        ]);

        $this->assertFalse($execStep1->is_signed);
        $this->assertTrue($execStep2->is_signed);
        $this->assertEquals('Directora General', $execStep2->signature_data['signer_name']);

        $execution = new CircuitExecution([
            'title' => 'Circuito con Firmas',
            'status' => CircuitExecution::STATUS_IN_PROGRESS,
            'signed_document_path' => 'circuits/1/doc_firmado.pdf',
        ]);
        $execution->setRelation('steps', collect([$execStep1, $execStep2]));

        $this->assertTrue($execution->has_signatures);
        $this->assertEquals(1, $execution->signatures_count);
        $this->assertEquals(1, $execution->required_signatures_count);
        $this->assertTrue($execution->all_signatures_completed);
    }

    public function test_activity_and_expediente_have_circuit_relationships(): void
    {
        $activity = new Activity();
        $this->assertInstanceOf(MorphMany::class, $activity->circuitExecutions());
        $this->assertInstanceOf(MorphOne::class, $activity->activeCircuitExecution());
        $this->assertInstanceOf(BelongsTo::class, $activity->circuitTemplate());
        $this->assertContains('circuit_template_id', $activity->getFillable());

        $expediente = new Expediente();
        $this->assertInstanceOf(MorphMany::class, $expediente->circuitExecutions());
        $this->assertInstanceOf(MorphOne::class, $expediente->activeCircuitExecution());
        $this->assertInstanceOf(BelongsTo::class, $expediente->circuitTemplate());
        $this->assertContains('circuit_template_id', $expediente->getFillable());
    }

    public function test_circuit_execution_supports_soft_deletes(): void
    {
        $execution = new CircuitExecution();
        $traits = class_uses_recursive($execution);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $traits);
        $this->assertNull($execution->deleted_at);
    }
}

