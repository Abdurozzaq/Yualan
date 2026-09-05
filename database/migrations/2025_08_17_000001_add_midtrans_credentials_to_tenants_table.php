<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('midtrans_server_key')->nullable();
            $table->string('midtrans_client_key')->nullable();
            $table->string('midtrans_merchant_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['midtrans_server_key', 'midtrans_client_key', 'midtrans_merchant_id']);
        });
    }
};
