<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('plan_name');
            $table->date('expired_at');
            $table->string('transaction_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_invoices');
    }
};
