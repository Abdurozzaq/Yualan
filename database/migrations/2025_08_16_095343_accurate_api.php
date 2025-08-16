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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'accurate_access_token')) {
                $table->string('accurate_access_token')->nullable()->after('ipaymu_secret_key');
            }
            if (!Schema::hasColumn('tenants', 'accurate_refresh_token')) {
                $table->string('accurate_refresh_token')->nullable()->after('accurate_access_token');
            }
            if (!Schema::hasColumn('tenants', 'accurate_token_expires_at')) {
                $table->timestamp('accurate_token_expires_at')->nullable()->after('accurate_refresh_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'accurate_access_token')) {
                $table->dropColumn('accurate_access_token');
            }
            if (Schema::hasColumn('tenants', 'accurate_refresh_token')) {
                $table->dropColumn('accurate_refresh_token');
            }
            if (Schema::hasColumn('tenants', 'accurate_token_expires_at')) {
                $table->dropColumn('accurate_token_expires_at');
            }
        });
    }
};
