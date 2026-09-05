<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Carbon\Carbon;

class UpdateTenantSubscriptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:update-subscription-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update is_subscribed to false for tenants whose subscription_ends_at has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $updated = Tenant::where('is_subscribed', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now)
            ->update(['is_subscribed' => false]);

        $this->info("Updated $updated tenants' subscription status.");
    }
}
