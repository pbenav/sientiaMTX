<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuadrantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quadrants = [
            [
                'name' => 'Haz Primero',
                'slug' => 'haz-primero',
                'description' => 'Urgente e Importante',
                'color_hex' => '#EF4444',
                'color_description' => 'Rojo',
                'order' => 1
            ],
            [
                'name' => 'Planifica',
                'slug' => 'planifica',
                'description' => 'No Urgente pero Importante',
                'color_hex' => '#F59E0B',
                'color_description' => 'Naranja',
                'order' => 2
            ],
            [
                'name' => 'Delega',
                'slug' => 'delega',
                'description' => 'Urgente pero No Importante',
                'color_hex' => '#3B82F6',
                'color_description' => 'Azul',
                'order' => 3
            ],
            [
                'name' => 'Elimina',
                'slug' => 'elimina',
                'description' => 'No Urgente ni Importante',
                'color_hex' => '#6B7280',
                'color_description' => 'Gris',
                'order' => 4
            ]
        ];

        foreach ($quadrants as $quadrant) {
            \App\Models\Quadrant::firstOrCreate(
                ['slug' => $quadrant['slug']],
                $quadrant
            );
        }
    }
}
