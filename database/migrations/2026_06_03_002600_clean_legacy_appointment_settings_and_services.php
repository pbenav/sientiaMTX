<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpiar/migrar registros con team_id null en appointment_settings
        $settings = DB::table('appointment_settings')->whereNull('team_id')->get();
        foreach ($settings as $setting) {
            $user = User::find($setting->user_id);
            $team = $user ? ($user->firstTeamWithAppointments() ?: $user->teams()->first()) : null;

            if ($team) {
                // Si encontramos un equipo para el usuario, le asignamos el registro
                DB::table('appointment_settings')
                    ->where('id', $setting->id)
                    ->update(['team_id' => $team->id]);
            } else {
                // Si no tiene equipo asignado, borramos el registro huérfano
                DB::table('appointment_settings')
                    ->where('id', $setting->id)
                    ->delete();
            }
        }

        // 2. Limpiar/migrar registros con team_id null en appointment_services
        $services = DB::table('appointment_services')->whereNull('team_id')->get();
        foreach ($services as $service) {
            $user = User::find($service->user_id);
            $team = $user ? ($user->firstTeamWithAppointments() ?: $user->teams()->first()) : null;

            if ($team) {
                // Si encontramos un equipo para el usuario, le asignamos el registro
                DB::table('appointment_services')
                    ->where('id', $service->id)
                    ->update(['team_id' => $team->id]);
            } else {
                // Si no tiene equipo asignado, borramos el registro huérfano
                DB::table('appointment_services')
                    ->where('id', $service->id)
                    ->delete();
            }
        }

        // 3. Hacer que team_id sea no-nulo (NOT NULL) en ambas tablas
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(true)->change();
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(true)->change();
        });
    }
};
