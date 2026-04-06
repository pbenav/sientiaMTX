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
        Schema::table('users', function (Blueprint $table) {
            $table->string('working_area_name')->nullable()->after('energy_level');
            $table->decimal('location_lat', 10, 8)->nullable()->after('working_area_name');
            $table->decimal('location_lng', 11, 8)->nullable()->after('location_lat');
            $table->integer('impact_radius')->default(10)->after('location_lng'); // km
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['working_area_name', 'location_lat', 'location_lng', 'impact_radius']);
        });
    }
};
