<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Models\SaasSetting;

class TenantController extends Controller
{
    /**
     * Return minimal tenant info as JSON for sidebar/status widgets.
     */
    public function info(Request $request, string $tenantSlug)
    {
        $tenant = Tenant::with('pricingPlan')->where('slug', $tenantSlug)->firstOrFail();

        $isInternal = SaasSetting::get('trial_days', 'INTERNAL');

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'pricing_plan_id' => $tenant->pricing_plan_id,
            'plan_name' => optional($tenant->pricingPlan)->plan_name,
            'is_subscribed' => (bool) $tenant->is_subscribed,
            'subscription_ends_at' => optional($tenant->subscription_ends_at)?->toDateString(),
            'isInternal' => $isInternal
        ]);
    }
}
