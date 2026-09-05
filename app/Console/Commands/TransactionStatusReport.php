<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class TransactionStatusReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yualan:transaction-report 
                            {--hours=24 : Rentang waktu dalam jam untuk laporan}
                            {--tenant= : Filter berdasarkan tenant slug (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menampilkan laporan status transaksi iPaymu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $tenantSlug = $this->option('tenant');
        
        $this->info("Laporan Status Transaksi iPaymu");
        $this->info("Periode: {$hours} jam terakhir");
        
        if ($tenantSlug) {
            $this->info("Tenant: {$tenantSlug}");
        }
        
        $this->line(str_repeat('=', 60));

        // Build base query
        $query = Sale::where('payment_method', 'ipaymu')
            ->where('created_at', '>=', now()->subHours($hours));

        if ($tenantSlug) {
            $query->whereHas('tenant', function($q) use ($tenantSlug) {
                $q->where('slug', $tenantSlug);
            });
        }

        // Get status summary
        $statusSummary = (clone $query)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total_amount'))
            ->groupBy('status')
            ->get();

        $this->table(
            ['Status', 'Jumlah Transaksi', 'Total Amount (IDR)'],
            $statusSummary->map(function($item) {
                return [
                    ucfirst($item->status),
                    number_format($item->count),
                    'Rp ' . number_format($item->total_amount, 0, ',', '.')
                ];
            })
        );

        // Get detailed pending transactions
        $pendingTransactions = (clone $query)
            ->where('status', 'pending')
            ->with(['tenant', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingTransactions->isNotEmpty()) {
            $this->line("\nTransaksi Pending Detail:");
            $this->line(str_repeat('-', 60));
            
            $this->table(
                ['Invoice', 'Tenant', 'Amount', 'Created', 'Hours Ago', 'Transaction ID'],
                $pendingTransactions->map(function($sale) {
                    $payment = $sale->payments()->where('payment_method', 'ipaymu')->first();
                    $hoursAgo = $sale->created_at->diffInHours(now());
                    
                    return [
                        $sale->invoice_number,
                        $sale->tenant->name ?? 'Unknown',
                        'Rp ' . number_format($sale->total_amount, 0, ',', '.'),
                        $sale->created_at->format('Y-m-d H:i'),
                        $hoursAgo . 'h',
                        $payment->transaction_id ?? 'N/A'
                    ];
                })
            );
        }

        // Get recent failed transactions
        $failedTransactions = (clone $query)
            ->where('status', 'failed')
            ->with(['tenant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($failedTransactions->isNotEmpty()) {
            $this->line("\nTransaksi Gagal Terbaru:");
            $this->line(str_repeat('-', 60));
            
            $this->table(
                ['Invoice', 'Tenant', 'Amount', 'Created', 'Notes'],
                $failedTransactions->map(function($sale) {
                    return [
                        $sale->invoice_number,
                        $sale->tenant->name ?? 'Unknown',
                        'Rp ' . number_format($sale->total_amount, 0, ',', '.'),
                        $sale->created_at->format('Y-m-d H:i'),
                        $sale->notes ? substr($sale->notes, 0, 50) . '...' : '-'
                    ];
                })
            );
        }

        // Performance metrics
        $totalTransactions = (clone $query)->count();
        $completedTransactions = (clone $query)->where('status', 'completed')->count();
        $pendingCount = $pendingTransactions->count();
        $failedCount = (clone $query)->where('status', 'failed')->count();
        
        $successRate = $totalTransactions > 0 ? ($completedTransactions / $totalTransactions) * 100 : 0;
        
        $this->line("\nMetrik Performa:");
        $this->line(str_repeat('-', 60));
        $this->info("Total Transaksi: " . number_format($totalTransactions));
        $this->info("Success Rate: " . number_format($successRate, 2) . "%");
        $this->info("Completed: " . number_format($completedTransactions));
        $this->warn("Pending: " . number_format($pendingCount));
        $this->error("Failed: " . number_format($failedCount));

        if ($pendingCount > 0) {
            $this->line("");
            $this->warn("💡 Ada {$pendingCount} transaksi pending. Jalankan:");
            $this->line("   php artisan yualan:check-pending-transactions");
        }

        return 0;
    }
}
