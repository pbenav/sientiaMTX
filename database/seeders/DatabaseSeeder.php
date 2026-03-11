<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\TeamRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        // 1. Roles y cuadrantes
        $this->call([
            TeamRoleSeeder::class,
            QuadrantSeeder::class,
        ]);

        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        $memberRole      = TeamRole::where('name', 'user')->first();

        // 2. Usuario administrador / coordinador del equipo
        $admin = User::updateOrCreate(
            ['email' => 'admin@sientia.com'],
            [
                'name'              => 'Carlos Martínez',
                'password'          => Hash::make('12345678'),
                'locale'            => 'es',
                'timezone'          => 'Europe/Madrid',
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Miembros del equipo demo
        $ana = User::firstOrCreate(
            ['email' => 'ana@sientia.com'],
            [
                'name'              => 'Ana García',
                'password'          => Hash::make('12345678'),
                'locale'            => 'es',
                'timezone'          => 'Europe/Madrid',
                'email_verified_at' => now(),
            ]
        );

        $pedro = User::firstOrCreate(
            ['email' => 'pedro@sientia.com'],
            [
                'name'              => 'Pedro Sánchez',
                'password'          => Hash::make('12345678'),
                'locale'            => 'es',
                'timezone'          => 'Europe/Madrid',
                'email_verified_at' => now(),
            ]
        );

        $lucia = User::firstOrCreate(
            ['email' => 'lucia@sientia.com'],
            [
                'name'              => 'Lucía Fernández',
                'password'          => Hash::make('12345678'),
                'locale'            => 'es',
                'timezone'          => 'Europe/Madrid',
                'email_verified_at' => now(),
            ]
        );

        // 4. Equipo demo
        $team = Team::firstOrCreate(
            ['slug' => 'sientia-demo'],
            [
                'uuid'          => (string) Str::uuid(),
                'name'          => 'Equipo Sientia',
                'description'   => 'Equipo de demostración para mostrar las funcionalidades de la Matriz de Eisenhower y gestión de tareas.',
                'created_by_id' => $admin->id,
            ]
        );

        // Adjuntar miembros si no existen ya
        foreach ([
            $admin->id  => $coordinatorRole->id,
            $ana->id    => $memberRole->id,
            $pedro->id  => $memberRole->id,
            $lucia->id  => $memberRole->id,
        ] as $userId => $roleId) {
            if (!$team->members()->where('user_id', $userId)->exists()) {
                $team->members()->attach($userId, ['role_id' => $roleId]);
            }
        }

        // 5. Tareas de demostración
        // Estructura: propietario = $admin (coordinador), sin autoasignarse.
        // Las tareas de grupo tienen instancias privadas para cada miembro asignado.
        // Las tareas individuales son propias del admin (sin instancias).

        // ── Q1: Importante y Urgente ────────────────────────────────────────────
        $this->createTask($team, $admin, [
            'title'       => 'Resolver caída del servidor de producción',
            'priority'    => 'critical',
            'urgency'     => 'critical',
            'status'      => 'in_progress',
            'due_date'    => now()->addHours(4),
            'description' => implode("\n", [
                '### Caída crítica en producción',
                '',
                'El servidor principal está caído. Afecta a todos los usuarios activos.',
                '',
                '**Acciones inmediatas:**',
                '- Revisar los logs de la base de datos',
                '- Reiniciar los contenedores de la aplicación',
                '- Notificar al equipo de operaciones',
            ]),
            'observations' => '> [!IMPORTANT]' . "\n" . '> Esta incidencia debe resolverse en menos de 2 horas para cumplir con el SLA.',
        ], assignTo: [$ana->id, $pedro->id]);

        $this->createTask($team, $admin, [
            'title'       => 'Responder a incidente de seguridad del cliente',
            'priority'    => 'high',
            'urgency'     => 'critical',
            'status'      => 'pending',
            'due_date'    => now()->addHours(8),
            'description' => implode("\n", [
                'Se ha detectado una posible brecha de seguridad en el módulo de autenticación.',
                '',
                '*Revisar los siguientes ficheros:*',
                '- `app/Http/Controllers/Auth/LoginController.php`',
                '- `routes/web.php`',
            ]),
            'observations' => 'Solicitado por el equipo de auditoría de seguridad. Usar los resultados del `Escáner de Vulnerabilidades` como referencia.',
        ], assignTo: [$lucia->id]);

        // ── Q2: Importante y No Urgente ─────────────────────────────────────────
        $this->createTask($team, $admin, [
            'title'       => 'Planificar hoja de ruta (roadmap) del segundo trimestre',
            'priority'    => 'high',
            'urgency'     => 'low',
            'status'      => 'pending',
            'due_date'    => now()->addDays(14),
            'description' => implode("\n", [
                'Es necesario planificar las funcionalidades e hitos del segundo trimestre del año.',
                '',
                '| Funcionalidad | Prioridad | Complejidad estimada |',
                '| :--- | :---: | :---: |',
                '| Sincronización con app móvil | Alta | Grande |',
                '| Priorización de tareas con IA | Media | Media |',
                '| Analíticas del equipo | Baja | Pequeña |',
            ]),
            'observations' => 'Revisar el documento de `Revisión del Q1` antes de comenzar.',
        ], assignTo: [$ana->id, $pedro->id, $lucia->id]);

        $this->createTask($team, $admin, [
            'title'       => 'Escribir documentación técnica de la API',
            'priority'    => 'critical',
            'urgency'     => 'medium',
            'status'      => 'pending',
            'due_date'    => now()->addDays(7),
            'description' => 'Falta la documentación técnica de la nueva API. Hay que documentar todos los endpoints usando la especificación OpenAPI.',
            'observations' => 'Usar Swagger o Postman para probar los endpoints durante la documentación.',
        ]); // Tarea propia del admin, sin asignados

        // ── Q3: No Importante y Urgente ─────────────────────────────────────────
        $this->createTask($team, $admin, [
            'title'       => 'Preparar y enviar el informe semanal',
            'priority'    => 'low',
            'urgency'     => 'high',
            'status'      => 'pending',
            'due_date'    => now()->addDays(2),
            'description' => 'Informe de progreso semanal para la dirección. Incluir métricas de sprint y estado de incidencias abiertas.',
            'observations' => 'Preparar el `Informe Semanal` con antelación.',
        ], assignTo: [$ana->id]);

        $this->createTask($team, $admin, [
            'title'       => 'Responder correos electrónicos de clientes pendientes',
            'priority'    => 'medium',
            'urgency'     => 'high',
            'status'      => 'pending',
            'due_date'    => now()->addDays(1),
            'description' => 'Revisar y responder los correos entrantes de clientes y socios.',
            'observations' => 'Priorizar la carpeta `Soporte` y los tickets marcados como urgentes.',
        ]); // Tarea propia

        // ── Q4: No Importante y No Urgente ──────────────────────────────────────
        $this->createTask($team, $admin, [
            'title'       => 'Reorganizar carpetas en la unidad compartida',
            'priority'    => 'low',
            'urgency'     => 'low',
            'status'      => 'pending',
            'due_date'    => now()->addDays(30),
            'description' => 'Limpiar y reorganizar las carpetas compartidas en Google Drive.',
            'observations' => 'Archivar los ficheros con más de 2 años de antigüedad.',
        ], assignTo: [$pedro->id]);

        $this->createTask($team, $admin, [
            'title'       => 'Revisar grabaciones de reuniones antiguas',
            'priority'    => 'medium',
            'urgency'     => 'low',
            'status'      => 'pending',
            'due_date'    => now()->addDays(21),
            'description' => 'Revisar las grabaciones de las reuniones de equipo pasadas para extraer decisiones clave.',
            'observations' => 'Subir las notas extraídas a la wiki interna.',
        ]); // Tarea propia
    }

    /**
     * Helper: crea una tarea (template si tiene asignados, simple si no).
     * El propietario ($owner) NUNCA aparece en $assignTo (es participante implícito).
     *
     * @param Team  $team
     * @param User  $owner        Creador/propietario de la tarea
     * @param array $data         Campos de la tarea
     * @param array $assignTo     IDs de usuarios a asignar (diferentes del propietario)
     */
    private function createTask(Team $team, User $owner, array $data, array $assignTo = []): void
    {
        // Evitar duplicados por título
        if ($team->tasks()->where('title', $data['title'])->exists()) {
            return;
        }

        // Filtrar por si acaso el propietario hubiera sido incluido
        $assignTo = array_filter($assignTo, fn ($id) => $id !== $owner->id);

        $isTemplate = !empty($assignTo);

        $task = $team->tasks()->create(array_merge($data, [
            'uuid'              => (string) Str::uuid(),
            'created_by_id'     => $owner->id,
            'is_template'       => $isTemplate,
            'scheduled_date'    => $data['scheduled_date'] ?? now(),
            'original_due_date' => $data['due_date'] ?? now()->addDays(7),
        ]));

        if ($isTemplate) {
            foreach ($assignTo as $userId) {
                // Registro de asignación (tabla task_assignments)
                $task->assignments()->create([
                    'user_id'        => $userId,
                    'assigned_by_id' => $owner->id,
                    'assigned_at'    => now(),
                ]);

                // Instancia privada para el miembro
                $team->tasks()->create([
                    'uuid'              => (string) Str::uuid(),
                    'title'             => $task->title,
                    'description'       => $task->description,
                    'priority'          => $task->priority,
                    'urgency'           => $task->urgency,
                    'status'            => 'pending',
                    'scheduled_date'    => $task->scheduled_date,
                    'due_date'          => $task->due_date,
                    'original_due_date' => $task->due_date,
                    'created_by_id'     => $owner->id,
                    'observations'      => null,
                    'parent_id'         => $task->id,
                    'is_template'       => false,
                    'assigned_user_id'  => $userId,
                    'visibility'        => 'private',
                ]);
            }
        }
    }
}
