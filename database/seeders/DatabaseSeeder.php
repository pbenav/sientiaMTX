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
        $admin = User::firstOrCreate(
            ['email' => 'admin@sientia.com'],
            [
                'name'     => 'Admin Sientia',
                'password' => Hash::make('12345678'),
                'locale'   => 'es',
                'timezone' => 'Europe/Madrid',
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
            // Q1 – Important + Urgent
            ['title' => 'Fix production server outage', 'priority' => 'critical', 'urgency' => 'critical', 'status' => 'in_progress'],
            ['title' => 'Respond to client security incident', 'priority' => 'high', 'urgency' => 'critical', 'status' => 'pending'],
            // Q2 – Important + Not Urgent
            ['title' => 'Plan Q2 product roadmap', 'priority' => 'high', 'urgency' => 'low', 'status' => 'pending'],
            ['title' => 'Write technical documentation', 'priority' => 'critical', 'urgency' => 'medium', 'status' => 'pending'],
            // Q3 – Not Important + Urgent
            ['title' => 'Schedule team meeting', 'priority' => 'low', 'urgency' => 'high', 'status' => 'pending'],
            ['title' => 'Reply to routine emails', 'priority' => 'medium', 'urgency' => 'high', 'status' => 'pending'],
            // Q4 – Not Important + Not Urgent
            ['title' => 'Reorganize shared drive folders', 'priority' => 'low', 'urgency' => 'low', 'status' => 'pending'],
            ['title' => 'Review old meeting recordings', 'priority' => 'medium', 'urgency' => 'low', 'status' => 'pending'],
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
