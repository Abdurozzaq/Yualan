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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['percentage', 'percentage_max', 'nominal']);
            $table->decimal('value', 12, 2);
            $table->decimal('max_nominal', 12, 2)->nullable();
            $table->date('expiry_date');
            // removed is_active column
            $table->boolean('used')->default(false); // voucher hanya bisa dipakai 1 kali
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
