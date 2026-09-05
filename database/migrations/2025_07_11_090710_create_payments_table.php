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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sale_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('IDR');
            $table->string('status')->default('pending');
            $table->string('transaction_id')->unique()->nullable();
            $table->jsonb('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
