<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('order_id')->nullable()->unique()->after('invoice_number');
            $table->string('midtrans_transaction_id')->nullable()->after('order_id');
            $table->string('payment_status')->nullable()->after('status');
            $table->string('payment_type')->nullable()->after('payment_status');
            $table->decimal('gross_amount', 12, 2)->nullable()->after('payment_type');
            $table->longText('midtrans_payload')->nullable()->after('gross_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'order_id',
                'midtrans_transaction_id',
                'payment_status',
                'payment_type',
                'gross_amount',
                'midtrans_payload',
            ]);
        });
    }
};
