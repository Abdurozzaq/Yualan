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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
                $table->uuid('tenant_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['buyxgetx', 'buyxgetanother']);
            $table->integer('buy_qty');
            $table->integer('get_qty');
            $table->uuid('product_id')->nullable();
            $table->uuid('another_product_id')->nullable();
            $table->date('expiry_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
