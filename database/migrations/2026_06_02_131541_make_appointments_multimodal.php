<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('modality')->nullable()->after('service_id')->comment('Modalidad elegida por el ciudadano');
        });

        // Modificamos appointment_services para que modality sea json, permitiendo varios modos
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->json('modalities')->nullable()->after('modality')->comment('Array de modalidades');
        });

        // Migrate data
        if (DB::getDriverName() === 'sqlite') {
            DB::table('appointment_services')->whereNotNull('modality')->get()->each(function ($service) {
                DB::table('appointment_services')
                    ->where('id', $service->id)
                    ->update(['modalities' => json_encode([$service->modality])]);
            });
            
            DB::table('appointments')->get()->each(function ($appointment) {
                $service = DB::table('appointment_services')->where('id', $appointment->service_id)->first();
                if ($service) {
                    DB::table('appointments')
                        ->where('id', $appointment->id)
                        ->update(['modality' => $service->modality]);
                }
            });
        } else {
            \DB::statement("UPDATE appointment_services SET modalities = JSON_ARRAY(modality) WHERE modality IS NOT NULL");
            \DB::statement("UPDATE appointments JOIN appointment_services ON appointments.service_id = appointment_services.id SET appointments.modality = appointment_services.modality");
        }

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
        
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->renameColumn('modalities', 'modality');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->string('old_modality')->nullable()->after('modality');
        });
        
        if (DB::getDriverName() === 'sqlite') {
            DB::table('appointment_services')->get()->each(function ($service) {
                $mods = json_decode($service->modality, true);
                $first = is_array($mods) && count($mods) > 0 ? $mods[0] : null;
                DB::table('appointment_services')
                    ->where('id', $service->id)
                    ->update(['old_modality' => $first]);
            });
        } else {
            \DB::statement("UPDATE appointment_services SET old_modality = JSON_UNQUOTE(JSON_EXTRACT(modality, '$[0]'))");
        }
        
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
        
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->renameColumn('old_modality', 'modality');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
    }
};
