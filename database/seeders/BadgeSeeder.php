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
                'name' => 'Primer Acceso',
                'description' => 'Otorgada por iniciar sesión por primera vez en SientiaMTX.',
                'icon' => '🔑',
                'criteria_type' => 'login_count',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Maestro de la Racha',
                'description' => 'Has mantenido una racha de conexión de 7 días consecutivos.',
                'icon' => '🔥',
                'criteria_type' => 'streak_days',
                'criteria_threshold' => 7,
            ],
            [
                'name' => 'Jugador de Equipo',
                'description' => 'Has colaborado en al menos 5 tareas asignadas a múltiples usuarios.',
                'icon' => '🤝',
                'criteria_type' => 'shared_tasks',
                'criteria_threshold' => 5,
            ],
            [
                'name' => 'Alto Rendimiento',
                'description' => 'Has completado 50 tareas con éxito.',
                'icon' => '🏆',
                'criteria_type' => 'completed_tasks',
                'criteria_threshold' => 50,
            ],
            [
                'name' => 'Reina de Kudos',
                'description' => 'Has recibido Kudos de tus compañeros 10 veces.',
                'icon' => '💌',
                'criteria_type' => 'received_kudos',
                'criteria_threshold' => 10,
            ],
            [
                'name' => 'Coleccionista de Medallas',
                'description' => 'Has coleccionado 5 medallas diferentes.',
                'icon' => '🏅',
                'criteria_type' => 'badges_count',
                'criteria_threshold' => 5,
            ],
            [
                'name' => 'Búho Nocturno',
                'description' => 'Has completado una tarea de madrugada (entre la medianoche y las 6 AM).',
                'icon' => '🦉',
                'criteria_type' => 'night_tasks',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Pájaro Madrugador',
                'description' => 'Eres el primero en completar una tarea por la mañana (antes de las 8 AM).',
                'icon' => '🕊️',
                'criteria_type' => 'early_tasks',
                'criteria_threshold' => 1,
            ],
            [
                'name' => 'Hito',
                'description' => 'Has alcanzado los 10,000 XP (Puntos de Experiencia).',
                'icon' => '🎯',
                'criteria_type' => 'xp_total',
                'criteria_threshold' => 10000,
            ],
            [
                'name' => 'Constante',
                'description' => 'Has cerrado al menos una tarea al día durante todo un mes.',
                'icon' => '📅',
                'criteria_type' => 'consistent_month',
                'criteria_threshold' => 30,
            ],
            [
                'name' => 'Ayudante',
                'description' => 'Has completado tareas que estaban bloqueadas o fuera de tu zona de confort (Resilience).',
                'icon' => '🛟',
                'criteria_type' => 'resilience_points',
                'criteria_threshold' => 100,
            ],
            [
                'name' => 'Leyenda',
                'description' => 'Has alcanzado el máximo nivel de reputación y desempeño. Nivel 50.',
                'icon' => '🌟',
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
