<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            ['name' => 'Derecho', 'category' => 'Jurídico', 'icon' => 'scale'],
            ['name' => 'Pedagogía', 'category' => 'Educación', 'icon' => 'academic-cap'],
            ['name' => 'Psicología', 'category' => 'Salud', 'icon' => 'heart'],
            ['name' => 'Animación Sociocultural', 'category' => 'Social', 'icon' => 'user-group'],
            ['name' => 'Docencia', 'category' => 'Educación', 'icon' => 'presentation-chart-line'],
            ['name' => 'Internet', 'category' => 'Tecnología', 'icon' => 'globe-alt'],
            ['name' => 'Informática', 'category' => 'Tecnología', 'icon' => 'device-tablet'],
            ['name' => 'Asesoramiento Fiscal', 'category' => 'Gestión', 'icon' => 'banknotes'],
            ['name' => 'Laboral', 'category' => 'Gestión', 'icon' => 'briefcase'],
            ['name' => 'Jurídico General', 'category' => 'Jurídico', 'icon' => 'shield-check'],
        ];

        foreach ($skills as $skill) {
            DB::table('skills')->updateOrInsert(
                ['slug' => Str::slug($skill['name'])],
                array_merge($skill, [
                    'slug' => Str::slug($skill['name']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
