<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'First Login',
                'description' => 'Otorgada por iniciar sesión por primera vez en SientiaMTX.',
                'icon' => 'fa-key',
                'criteria_type' => 'login_count',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Streak Master',
                'description' => 'Has mantenido una racha de conexión de 7 días consecutivos.',
                'icon' => 'fa-fire',
                'criteria_type' => 'streak_days',
                'criteria_threshold' => 7,
            ],
            [
                'name' => 'Team Player',
                'description' => 'Has colaborado en al menos 5 tareas asignadas a múltiples usuarios.',
                'icon' => 'fa-handshake',
                'criteria_type' => 'shared_tasks',
                'criteria_threshold' => 5,
            ],
            [
                'name' => 'Top Performer',
                'description' => 'Has completado 50 tareas con éxito.',
                'icon' => 'fa-trophy',
                'criteria_type' => 'completed_tasks',
                'criteria_threshold' => 50,
            ],
            [
                'name' => 'Kudos Queen',
                'description' => 'Has recibido Kudos de tus compañeros 10 veces.',
                'icon' => 'fa-envelope-open-text',
                'criteria_type' => 'received_kudos',
                'criteria_threshold' => 10,
            ],
            [
                'name' => 'Badge Collector',
                'description' => 'Has coleccionado 5 medallas diferentes.',
                'icon' => 'fa-medal',
                'criteria_type' => 'badges_count',
                'criteria_threshold' => 5,
            ],
            [
                'name' => 'Night Owl',
                'description' => 'Has completado una tarea de madrugada (entre la medianoche y las 6 AM).',
                'icon' => 'fa-owl',
                'criteria_type' => 'night_tasks',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Early Bird',
                'description' => 'Eres el primero en completar una tarea por la mañana (antes de las 8 AM).',
                'icon' => 'fa-dove',
                'criteria_type' => 'early_tasks',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Milestone',
                'description' => 'Has alcanzado los 10,000 XP (Puntos de Experiencia).',
                'icon' => 'fa-bullseye',
                'criteria_type' => 'xp_total',
                'criteria_threshold' => 10000,
            ],
            [
                'name' => 'Consistent',
                'description' => 'Has cerrado al menos una tarea al día durante todo un mes.',
                'icon' => 'fa-calendar-check',
                'criteria_type' => 'consistent_month',
                'criteria_threshold' => 30,
            ],
            [
                'name' => 'Helper',
                'description' => 'Has completado tareas que estaban bloqueadas o fuera de tu zona de confort (Resilience).',
                'icon' => 'fa-life-ring',
                'criteria_type' => 'resilience_points',
                'criteria_threshold' => 100,
            ],
            [
                'name' => 'Legend',
                'description' => 'Has alcanzado el máximo nivel de reputación y desempeño. Nivel 50.',
                'icon' => 'fa-star',
                'criteria_type' => 'user_level',
                'criteria_threshold' => 50,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['name' => $badge['name']],
                $badge
            );
        }
    }
}
