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
            $table->string('cth_api_url')->nullable()->after('work_days_2');
            $table->text('cth_api_token')->nullable()->after('cth_api_url');
            $table->string('cth_user_code')->nullable()->after('cth_api_token');
            $table->string('cth_work_center_code')->nullable()->after('cth_user_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cth_api_url',
                'cth_api_token',
                'cth_user_code',
                'cth_work_center_code'
            ]);
        });
    }
};
