<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Jadwalkan command update subscription setiap hari
        $schedule->command('tenant:update-subscription-status')->daily();
        
        // Jadwalkan pengecekan status transaksi iPaymu yang pending
        // Jalankan setiap 5 menit untuk transaksi dalam 24 jam terakhir
        $schedule->command('yualan:check-pending-transactions --limit=100 --hours=24')
                ->everyFiveMinutes()
                ->withoutOverlapping(10) // Prevent overlapping executions
                ->runInBackground();
        
        // Jalankan pengecekan yang lebih intensif setiap jam untuk transaksi dalam 72 jam terakhir
        $schedule->command('yualan:check-pending-transactions --limit=200 --hours=72')
                ->hourly()
                ->withoutOverlapping(15)
                ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
