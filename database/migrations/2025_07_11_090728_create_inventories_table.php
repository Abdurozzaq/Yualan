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
        Schema::create('inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->integer('quantity_change'); // Positive for incoming, negative for outgoing
            $table->string('type')->comment('in, out, adjustment, return'); // Type of inventory movement
            $table->text('reason')->nullable();
            $table->uuid('source_id')->nullable(); // Optional: Link to sale_item_id, purchase_order_id, etc.
            $table->string('source_type')->nullable(); // Optional: Type of source (e.g., App\Models\SaleItem)
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
