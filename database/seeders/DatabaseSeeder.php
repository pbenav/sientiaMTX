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
            ['title' => __('tasks.demo.fix_outage'), 'priority' => 'critical', 'urgency' => 'critical', 'status' => 'in_progress'],
            ['title' => __('tasks.demo.security_incident'), 'priority' => 'high', 'urgency' => 'critical', 'status' => 'pending'],
            // Q2 – Importante + No Urgente
            ['title' => __('tasks.demo.roadmap'), 'priority' => 'high', 'urgency' => 'low', 'status' => 'pending'],
            ['title' => __('tasks.demo.documentation'), 'priority' => 'critical', 'urgency' => 'medium', 'status' => 'pending'],
            // Q3 – No Importante + Urgente
            ['title' => __('tasks.demo.meeting'), 'priority' => 'low', 'urgency' => 'high', 'status' => 'pending'],
            ['title' => __('tasks.demo.emails'), 'priority' => 'medium', 'urgency' => 'high', 'status' => 'pending'],
            // Q4 – No Importante + No Urgente
            ['title' => __('tasks.demo.drive'), 'priority' => 'low', 'urgency' => 'low', 'status' => 'pending'],
            ['title' => __('tasks.demo.recordings'), 'priority' => 'medium', 'urgency' => 'low', 'status' => 'pending'],
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
