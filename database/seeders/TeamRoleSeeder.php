<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'coordinator',
                'description' => 'Coordinador: Puede crear/editar/borrar tareas, invitar usuarios, asignar roles, ver reportes y auditoría'
            ],
            [
                'name' => 'user',
                'description' => 'Usuario: Puede ver tareas asignadas, actualizar status propio, comentar'
            ]
        ];

        foreach ($roles as $role) {
            \App\Models\TeamRole::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
