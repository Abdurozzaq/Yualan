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
        Schema::table('products', function (Blueprint $table) {
            // Kolom untuk menyimpan harga pokok rata-rata produk
            $table->decimal('cost_price', 15, 2)->default(0.00)->after('price');
            // Kolom opsional untuk batas stok minimum
            $table->integer('min_stock_level')->nullable()->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
            $table->dropColumn('min_stock_level');
        });
    }
};
