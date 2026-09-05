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
        // Membuat tabel baru untuk menyimpan paket harga langganan
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plan_name');
            $table->string('plan_description')->nullable();
            $table->string('period_type')->comment('monthly, quarterly, yearly');
            $table->decimal('price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        // Memperbarui tabel tenants untuk melacak status langganan
        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('pricing_plan_id')->nullable()->after('is_active');
            $table->timestamp('subscription_ends_at')->nullable()->after('pricing_plan_id');
            $table->string('last_transaction_id')->nullable()->after('subscription_ends_at');
            $table->boolean('is_subscribed')->default(false)->after('last_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('pricing_plan_id');
            $table->dropColumn('subscription_ends_at');
            $table->dropColumn('last_transaction_id');
            $table->dropColumn('is_subscribed');
        });
    }
};

