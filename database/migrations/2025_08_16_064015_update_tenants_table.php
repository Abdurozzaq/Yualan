<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        // Periksa apakah tabel 'tenants' sudah ada
        if (!Schema::hasTable('tenants')) {
            // Jika tabel belum ada, buat dengan semua kolom
            Schema::create('tenants', function (Blueprint $table) {
                // Kolom-kolom utama yang tidak boleh null
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('invitation_code')->unique();
                $table->string('slug')->unique();
                $table->string('email')->unique();
                $table->string('business_type');

                // Kolom-kolom yang diizinkan null
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('country')->nullable();
                $table->string('ipaymu_api_key')->nullable();
                $table->string('ipaymu_secret_key')->nullable();
                $table->string('last_transaction_id')->nullable();
                $table->uuid('pricing_plan_id')->nullable();

                // Kolom-kolom dengan nilai default
                $table->boolean('is_active')->default(true);
                $table->string('ipaymu_mode')->default('sandbox');
                $table->boolean('is_subscribed')->default(false);

                // Kolom timestamp
                $table->timestamp('subscription_ends_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();

                // Kolom tambahan
                $table->string('owner_name')->nullable();
                $table->string('owner_email')->nullable();
                $table->string('subscription_status')->default('trial');
            });
        } else {
            // Jika tabel sudah ada, tambahkan kolom yang belum ada satu per satu.
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('tenants', 'address')) {
                    $table->text('address')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('tenants', 'city')) {
                    $table->string('city')->nullable()->after('address');
                }
                if (!Schema::hasColumn('tenants', 'state')) {
                    $table->string('state')->nullable()->after('city');
                }
                if (!Schema::hasColumn('tenants', 'zip_code')) {
                    $table->string('zip_code')->nullable()->after('state');
                }
                if (!Schema::hasColumn('tenants', 'country')) {
                    $table->string('country')->nullable()->after('zip_code');
                }
                if (!Schema::hasColumn('tenants', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('business_type');
                }
                if (!Schema::hasColumn('tenants', 'ipaymu_api_key')) {
                    $table->string('ipaymu_api_key')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('tenants', 'ipaymu_secret_key')) {
                    $table->string('ipaymu_secret_key')->nullable()->after('ipaymu_api_key');
                }
                if (!Schema::hasColumn('tenants', 'ipaymu_mode')) {
                    $table->string('ipaymu_mode')->default('sandbox')->after('ipaymu_secret_key');
                }
                if (!Schema::hasColumn('tenants', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('tenants', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
                if (!Schema::hasColumn('tenants', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }
                if (!Schema::hasColumn('tenants', 'pricing_plan_id')) {
                    $table->uuid('pricing_plan_id')->nullable()->after('deleted_at');
                }
                if (!Schema::hasColumn('tenants', 'subscription_ends_at')) {
                    $table->timestamp('subscription_ends_at')->nullable()->after('pricing_plan_id');
                }
                if (!Schema::hasColumn('tenants', 'last_transaction_id')) {
                    $table->string('last_transaction_id')->nullable()->after('subscription_ends_at');
                }
                if (!Schema::hasColumn('tenants', 'is_subscribed')) {
                    $table->boolean('is_subscribed')->default(false)->after('last_transaction_id');
                }
                // Tambahkan kolom baru
                if (!Schema::hasColumn('tenants', 'owner_name')) {
                    $table->string('owner_name')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('tenants', 'owner_email')) {
                    $table->string('owner_email')->nullable()->after('owner_name');
                }
                if (!Schema::hasColumn('tenants', 'subscription_status')) {
                    $table->string('subscription_status')->default('trial')->after('is_subscribed');
                }
            });
        }
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

