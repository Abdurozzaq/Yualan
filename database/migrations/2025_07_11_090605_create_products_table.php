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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('category_id')->nullable(); // Foreign key to categories table
            $table->string('name');
            $table->string('sku')->unique()->nullable(); // Stock Keeping Unit (optional, but good for uniqueness)
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Price of the product
            $table->integer('stock')->default(0); // Current stock level
            $table->string('unit')->nullable(); // e.g., pcs, kg, liter
            $table->string('image')->nullable(); // Path to product image

            // For restaurants:
            $table->boolean('is_food_item')->default(false); // If it's a food item (for restaurants)
            $table->text('ingredients')->nullable(); // For food items

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null'); // Set null if category is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
