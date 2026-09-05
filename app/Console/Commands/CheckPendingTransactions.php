<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Inventory;
use App\Services\IpaymuService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckPendingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yualan:check-pending-transactions 
                            {--limit=50 : Limit jumlah transaksi yang dicek dalam satu run}
                            {--hours=24 : Hanya cek transaksi yang dibuat dalam X jam terakhir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check dan update status transaksi iPaymu yang masih pending atau failed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan transaksi pending...');
        
        $limit = (int) $this->option('limit');
        $hours = (int) $this->option('hours');
        
        // Ambil transaksi yang statusnya pending atau failed dan menggunakan payment method iPaymu
        // Hanya ambil transaksi dalam rentang waktu tertentu untuk menghindari pengecekan transaksi lama
        $pendingSales = Sale::where('payment_method', 'ipaymu')
            ->whereIn('status', ['pending', 'failed'])
            ->where('created_at', '>=', now()->subHours($hours))
            ->with(['tenant', 'saleItems.product', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($pendingSales->isEmpty()) {
            $this->info('Tidak ada transaksi pending yang perlu dicek.');
            return 0;
        }

        $this->info("Ditemukan {$pendingSales->count()} transaksi pending untuk dicek.");
        
        $successCount = 0;
        $failedCount = 0;
        $unchangedCount = 0;

        foreach ($pendingSales as $sale) {
            $this->line("Mengecek transaksi: {$sale->invoice_number} (ID: {$sale->id})");
            
            try {
                $result = $this->checkSaleStatus($sale);
                
                switch ($result) {
                    case 'completed':
                        $successCount++;
                        $this->info("  ✓ Status diperbarui ke COMPLETED");
                        break;
                    case 'failed':
                        $failedCount++;
                        $this->warn("  ✗ Status diperbarui ke FAILED");
                        break;
                    default:
                        $unchangedCount++;
                        $this->line("  - Status tetap {$sale->status}");
                        break;
                }
                
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                Log::error("CheckPendingTransactions Error for Sale ID {$sale->id}: " . $e->getMessage(), [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'exception' => $e
                ]);
                $failedCount++;
            }
        }

        $this->info("\n=== RINGKASAN ===");
        $this->info("Berhasil diperbarui: {$successCount}");
        $this->warn("Gagal/Error: {$failedCount}");
        $this->line("Tidak berubah: {$unchangedCount}");
        $this->info("Total diproses: " . ($successCount + $failedCount + $unchangedCount));

        return 0;
    }

    /**
     * Check status for a specific sale
     * 
     * @param Sale $sale
     * @return string Status result: 'completed', 'failed', 'unchanged'
     */
    private function checkSaleStatus(Sale $sale): string
    {
        if (!$sale->tenant) {
            throw new \Exception("Tenant tidak ditemukan untuk sale ID: {$sale->id}");
        }

        // Cari transaction_id dari payment records atau gunakan alternative approach
        $payment = $sale->payments()->where('payment_method', 'ipaymu')
                                   ->latest()
                                   ->first();

        if (!$payment) {
            $this->error("  ❌ Tidak ada payment record untuk sale {$sale->invoice_number}");
            return 'unchanged';
        }

        // Jika sudah ada transaction_id, gunakan itu
        if ($payment->transaction_id) {
            return $this->checkSaleStatusWithTransactionId($sale, $payment->transaction_id);
        }

        // Jika tidak ada transaction_id, coba gunakan alternative approach
        // Gunakan referenceId (sale_id) untuk query iPaymu API
        $this->line("  🔍 Tidak ada transaction_id, menggunakan referenceId untuk pengecekan...");
        
        try {
            return $this->checkSaleStatusWithReferenceId($sale, $payment);
        } catch (\Exception $e) {
            // Log detail informasi untuk debugging
            Log::info("Failed to check via referenceId", [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'gateway_response_has_data' => isset($payment->gateway_response['Data']),
                'gateway_response_keys' => isset($payment->gateway_response['Data']) ? array_keys($payment->gateway_response['Data']) : null,
            ]);
            
            // Fallback ke old behavior dengan informative message
            $hoursSinceCreated = $payment->created_at->diffInHours(now());
            
            if ($hoursSinceCreated < 2) {
                $this->line("  ⏳ Payment baru diinisiasi {$hoursSinceCreated} jam yang lalu");
            } else {
                $this->warn("  ⚠️ Payment sudah {$hoursSinceCreated} jam, tidak ada transaction_id");
            }
            
            // Show SessionID if available
            $gatewayResponse = $payment->gateway_response;
            if (isset($gatewayResponse['Data']['SessionID'])) {
                $sessionId = $gatewayResponse['Data']['SessionID'];
                $this->line("  📋 SessionID iPaymu: {$sessionId}");
            }
            
            return 'unchanged';
        }

        $transactionId = $payment->transaction_id;
        
        return $this->checkSaleStatusWithTransactionId($sale, $transactionId);
    }

    /**
     * Check sale status with specific transaction ID
     * 
     * @param Sale $sale
     * @param string $transactionId
     * @return string Status result: 'completed', 'failed', 'unchanged'
     */
    private function checkSaleStatusWithTransactionId(Sale $sale, string $transactionId): string
    {
        // Initialize iPaymu service
        $ipaymuService = new IpaymuService($sale->tenant);
        
        // Check transaction status via iPaymu API
        $checkStatusResponse = $ipaymuService->checkTransaction($transactionId);
        
        if (!isset($checkStatusResponse['Data']['StatusDesc'])) {
            throw new \Exception("Response iPaymu tidak valid untuk transaction ID: {$transactionId}");
        }
        
        $ipaymuStatus = $checkStatusResponse['Data']['StatusDesc'];
        $ipaymuAmount = $checkStatusResponse['Data']['Amount'] ?? 0;
        
        Log::info("Transaction Status Check", [
            'sale_id' => $sale->id,
            'transaction_id' => $transactionId,
            'ipaymu_status' => $ipaymuStatus,
            'ipaymu_amount' => $ipaymuAmount,
            'current_sale_status' => $sale->status
        ]);

        // Get payment record for updating
        $payment = $sale->payments()->where('payment_method', 'ipaymu')
                                   ->where('transaction_id', $transactionId)
                                   ->first();

        // Update sale status based on iPaymu's actual status
        if ($ipaymuStatus === 'Berhasil') {
            if ($sale->status !== 'completed') {
                $this->updateSaleToCompleted($sale, $transactionId, $ipaymuAmount, $checkStatusResponse);
                return 'completed';
            }
        } elseif ($ipaymuStatus === 'Gagal') {
            if ($sale->status !== 'failed') {
                $this->updateSaleToFailed($sale, $transactionId, $checkStatusResponse);
                return 'failed';
            }
        } elseif ($ipaymuStatus === 'Pending') {
            // Update payment record but keep sale as pending
            if ($payment) {
                $this->updatePaymentRecord($payment, 'pending', $ipaymuAmount, $checkStatusResponse);
            }
        }

        return 'unchanged';
    }

    /**
     * Check sale status using referenceId (sale_id) instead of transaction_id
     */
    private function checkSaleStatusWithReferenceId(Sale $sale, Payment $payment): string
    {
        $this->line("  � Mencoba cari transaction_id via iPaymu history API...");
        
        // Initialize iPaymu service
        $ipaymuService = new IpaymuService($sale->tenant);
        
        try {
            // Look for transaction using reference ID (which should be the payment reference_id)
            $referenceId = $payment->reference_id ?? $sale->id;
            $this->line("  � Searching for reference_id: {$referenceId}");
            
            $transaction = $ipaymuService->findTransactionByReferenceId($referenceId);
            
            if (!$transaction) {
                $age = $payment->created_at->diffForHumans();
                $this->warn("    ❌ Transaction not found in iPaymu history");
                $this->warn("    📊 Payment Details:");
                $this->warn("      - Payment ID: {$payment->id}");
                $this->warn("      - Reference ID: {$referenceId}");
                $this->warn("      - Created: {$age}");
                
                // Show SessionID if available from gateway response
                $gatewayResponse = $payment->gateway_response;
                if (isset($gatewayResponse['Data']['SessionID'])) {
                    $sessionId = $gatewayResponse['Data']['SessionID'];
                    $this->warn("      - SessionID: {$sessionId}");
                }
                
                $this->line("    ℹ️  Customer may not have completed payment yet");
                return 'unchanged';
            }
            
            // Found transaction, extract transaction_id
            $transactionId = $transaction['TransactionId'];
            $this->info("    ✅ Found transaction_id via history API: {$transactionId}");
            
            // Update payment record with transaction_id
            $payment->update([
                'transaction_id' => $transactionId
            ]);
            
            $this->info("    📝 Updated payment record with transaction_id");
            
            // Now check the actual transaction status
            return $this->checkSaleStatusWithTransactionId($sale, $transactionId);
            
        } catch (\Exception $e) {
            $this->error("    ❌ Error searching via history API: " . $e->getMessage());
            Log::error("History API search failed for sale {$sale->id}", [
                'error' => $e->getMessage(),
                'reference_id' => $referenceId ?? 'N/A',
                'payment_id' => $payment->id
            ]);
            
            return 'unchanged';
        }
    }

    /**
     * Update sale to completed status
     */
    private function updateSaleToCompleted(Sale $sale, string $transactionId, float $amount, array $response): void
    {
        // Update sale status
        $sale->update([
            'status' => 'completed',
            'paid_amount' => $amount,
            'change_amount' => $amount - $sale->total_amount,
            'notes' => 'Pembayaran berhasil via iPaymu (Scheduler Check - TRX ID: ' . $transactionId . ')',
        ]);

        // Update payment record
        $payment = Payment::where('sale_id', $sale->id)
                          ->where('transaction_id', $transactionId)
                          ->first();
        
        if ($payment) {
            $this->updatePaymentRecord($payment, 'completed', $amount, $response);
        }

        // Log inventory movements if not already logged
        $this->logInventoryMovements($sale);

        Log::info("Sale updated to completed via scheduler", [
            'sale_id' => $sale->id,
            'transaction_id' => $transactionId,
            'amount' => $amount
        ]);
    }

    /**
     * Update sale to failed status
     */
    private function updateSaleToFailed(Sale $sale, string $transactionId, array $response): void
    {
        $sale->update([
            'status' => 'failed',
            'notes' => 'Pembayaran gagal via iPaymu (Scheduler Check - TRX ID: ' . $transactionId . ')',
        ]);

        // Update payment record
        $payment = Payment::where('sale_id', $sale->id)
                          ->where('transaction_id', $transactionId)
                          ->first();
        
        if ($payment) {
            $this->updatePaymentRecord($payment, 'failed', 0, $response);
        }

        Log::info("Sale updated to failed via scheduler", [
            'sale_id' => $sale->id,
            'transaction_id' => $transactionId
        ]);
    }

    /**
     * Update payment record
     */
    private function updatePaymentRecord(Payment $payment, string $status, float $amount, array $response): void
    {
        $payment->update([
            'status' => $status,
            'amount' => $amount,
            'gateway_response' => $response,
            'notes' => "Status diperbarui via scheduler check pada " . now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log inventory movements for completed sale
     */
    private function logInventoryMovements(Sale $sale): void
    {
        // Check if inventory movements already exist for this sale using source_id
        $saleItemIds = $sale->saleItems()->pluck('id');
        
        $existingMovements = Inventory::where('source_type', 'App\\Models\\SaleItem')
                                    ->whereIn('source_id', $saleItemIds)
                                    ->exists();

        if ($existingMovements) {
            return; // Already logged
        }

        // Load sale items if not already loaded
        if (!$sale->relationLoaded('saleItems')) {
            $sale->load('saleItems.product');
        }

        foreach ($sale->saleItems as $saleItem) {
            Inventory::create([
                'id' => Str::uuid(),
                'tenant_id' => $sale->tenant_id,
                'product_id' => $saleItem->product_id,
                'quantity_change' => -$saleItem->quantity,
                'type' => 'out',
                'reason' => 'Penjualan iPaymu (Scheduler): ' . $sale->invoice_number,
                'source_id' => $saleItem->id,
                'source_type' => 'App\\Models\\SaleItem',
            ]);
        }

        Log::info("Inventory movements logged for completed sale via scheduler", [
            'sale_id' => $sale->id,
            'items_count' => $sale->saleItems->count()
        ]);
    }
}
