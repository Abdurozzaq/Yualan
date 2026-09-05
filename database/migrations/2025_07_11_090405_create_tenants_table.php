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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID for tenant ID
            $table->string('name');
            $table->string('invitation_code')->unique();
            $table->string('slug')->unique(); // Unique slug for subdomain/path routing (e.g., myshop.yourdomain.com)
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->string('business_type')->comment('e.g., store, restaurant, minimarket'); // For differentiation
            $table->boolean('is_active')->default(true);

            // iPaymu configuration per tenant
            $table->string('ipaymu_api_key')->nullable();
            $table->string('ipaymu_secret_key')->nullable();
            $table->string('ipaymu_mode')->default('sandbox')->comment('sandbox or production'); // iPaymu environment

            $table->timestamps();
            $table->softDeletes(); // For soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
