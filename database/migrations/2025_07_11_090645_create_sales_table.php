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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Who performed the sale (cashier)
            $table->uuid('customer_id')->nullable(); // Optional customer for the sale
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0); // Amount paid by customer
            $table->decimal('change_amount', 10, 2)->default(0); // Change given to customer
            $table->string('payment_method'); // e.g., cash, card, iPaymu
            $table->string('status')->default('completed')->comment('completed, pending, cancelled, refunded');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
