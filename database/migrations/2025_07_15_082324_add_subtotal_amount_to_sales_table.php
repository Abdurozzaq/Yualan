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
        Schema::table('sales', function (Blueprint $table) {
            // Tambahkan kolom subtotal_amount setelah invoice_number
            // Dengan nilai default 0 untuk baris yang sudah ada
            $table->decimal('subtotal_amount', 15, 2)->default(0.00)->after('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('subtotal_amount');
        });
    }
};
