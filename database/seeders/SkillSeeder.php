<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            [1, 'Pedagogía', '🎓', '#8b5cf6'],
            [2, 'Soporte Técnico', '🛠️', '#3b82f6'],
            [3, 'Administración', '📋', '#64748b'],
            [4, 'Gestión Emocional', '🧠', '#ec4899'],
            [5, 'Impacto Rural', '🌲', '#10b981'],
            [6, 'Derecho', '⚖️', '#b91c1c'],
            [7, 'Psicología', '💜', '#d946ef'],
            [8, 'Animación Sociocultural', '🎭', '#f59e0b'],
            [9, 'Docencia', '👨‍🏫', '#06b6d4'],
            [10, 'Internet', '🌐', '#3b82f6'],
            [11, 'Informática', '💻', '#0ea5e9'],
            [12, 'Asesoramiento Fiscal', '💰', '#f43f5e'],
            [13, 'Laboral', '💼', '#f97316'],
            [14, 'Jurídico General', '📜', '#475569'],
        ];

        foreach ($skills as $data) {
            Skill::updateOrCreate(
                ['id' => $data[0]],
                [
                    'name' => $data[1],
                    'slug' => \Illuminate\Support\Str::slug($data[1]),
                    'icon' => $data[2],
                    'color' => $data[3],
                ]
            );
        }
    }
}
