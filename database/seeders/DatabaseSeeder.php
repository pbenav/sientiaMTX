<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\TeamRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        // 1. Roles and quadrants first
        $this->call([
            TeamRoleSeeder::class,
            QuadrantSeeder::class,
        ]);

        // 2. Demo admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@sientia.com'],
            [
                'name'     => 'Admin Sientia',
                'password' => Hash::make('12345678'),
                'locale'   => 'es',
                'timezone' => 'Europe/Madrid',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Demo regular user
        $demo = User::firstOrCreate(
            ['email' => 'demo@sientia.com'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('12345678'),
                'locale'   => 'en',
                'timezone' => 'UTC',
                'email_verified_at' => now(),
            ]
        );

        // 4. Demo team
        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        $memberRole = TeamRole::where('name', 'user')->first();

        $team = Team::firstOrCreate(
            ['slug' => 'sientia-demo'],
            [
                'uuid'          => (string) \Illuminate\Support\Str::uuid(),
                'name'          => 'Sientia Demo',
                'description'   => 'Demo team to showcase the Eisenhower Matrix features.',
                'created_by_id' => $admin->id,
            ]
        );

        // Attach members if not already
        if (!$team->members()->where('user_id', $admin->id)->exists()) {
            $team->members()->attach($admin->id, ['role_id' => $coordinatorRole->id]);
        }
        if (!$team->members()->where('user_id', $demo->id)->exists()) {
            $team->members()->attach($demo->id, ['role_id' => $memberRole->id]);
        }

        // 5. Sample tasks covering all 4 quadrants
        $tasksData = [
            // Q1 – Importante + Urgente
            [
                'title' => __('tasks.demo.fix_outage'),
                'priority' => 'critical',
                'urgency' => 'critical',
                'status' => 'in_progress',
                'description' => "### Critical Production Outage\n\nThe main server is currently down. This is affecting all users.\n\n**Immediate Actions:**\n- Check database logs\n- Restart application containers\n- Notify the devops team",
                'observations' => "> [!IMPORTANT]\n> This task must be resolved within the next 2 hours to meet SLA."
            ],
            [
                'title' => __('tasks.demo.security_incident'),
                'priority' => 'high',
                'urgency' => 'critical',
                'status' => 'pending',
                'description' => "A potential security breach has been reported in the auth module.\n\n*Review the following files:*\n- `app/Http/Controllers/Auth/LoginController.php`\n- `routes/web.php`",
                'observations' => "Requested by the security audit team. Use `Vulnerability Scanner` results as reference."
            ],
            // Q2 – Importante + No Urgente
            [
                'title' => __('tasks.demo.roadmap'),
                'priority' => 'high',
                'urgency' => 'low',
                'status' => 'pending',
                'description' => "We need to plan the features and milestones for the second quarter of the year.\n\n| Feature | Priority | Estimated Complexity |\n| :--- | :---: | :---: |\n| Mobile App Sync | High | Large |\n| AI Task Prioritization | Medium | Medium |\n| Team Analytics | Low | Small |",
                'observations' => "Check the `Q1 Review` document before starting."
            ],
            [
                'title' => __('tasks.demo.documentation'),
                'priority' => 'critical',
                'urgency' => 'medium',
                'status' => 'pending',
                'description' => "The technical documentation for the new API is missing. We need to document all endpoints using OpenAPI specification.",
                'observations' => "Use Swagger or Postman for testing the endpoints during documentation."
            ],
            // Q3 – No Importante + Urgente
            [
                'title' => __('tasks.demo.meeting'),
                'priority' => 'low',
                'urgency' => 'high',
                'status' => 'pending',
                'description' => "Routine meeting to discuss the weekly progress.",
                'observations' => "Prepare the `Weekly Report` beforehand."
            ],
            [
                'title' => __('tasks.demo.emails'),
                'priority' => 'medium',
                'urgency' => 'high',
                'status' => 'pending',
                'description' => "Check and respond to incoming emails from customers and partners.",
                'observations' => "Focus on the `Support` folder first."
            ],
            // Q4 – No Importante + No Urgente
            [
                'title' => __('tasks.demo.drive'),
                'priority' => 'low',
                'urgency' => 'low',
                'status' => 'pending',
                'description' => "Clean up and reorganize the shared folders in Google Drive.",
                'observations' => "Archive files older than 2 years."
            ],
            [
                'title' => __('tasks.demo.recordings'),
                'priority' => 'medium',
                'urgency' => 'low',
                'status' => 'pending',
                'description' => "Review the recordings from the past team meetings to extract key decisions.",
                'observations' => "Upload the extracted notes to the internal wiki."
            ],
        ];

        foreach ($tasksData as $t) {
            if (!$team->tasks()->where('title', $t['title'])->exists()) {
                $team->tasks()->create(array_merge($t, [
                    'uuid'              => (string) \Illuminate\Support\Str::uuid(),
                    'created_by_id'     => $admin->id,
                    'original_due_date' => now()->addDays(rand(1, 30)),
                    'due_date'          => now()->addDays(rand(1, 30)),
                ]));
            }
        }
    }
}
