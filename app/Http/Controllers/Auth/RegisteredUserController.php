<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi data akun pemilik
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Validasi data bisnis
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Tenant::class.',email'],
            'business_type' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
        ]);

        // Ambil jumlah hari trial dari saas_settings (atau default 7 hari)
        $trialDays = 7;
        $trialSetting = DB::table('saas_settings')->where('key', 'trial_days')->first();
        if ($trialSetting && is_numeric($trialSetting->value)) {
            $trialDays = (int) $trialSetting->value;
        }

        $now = now();
        $subscriptionEndsAt = $now->copy()->addDays($trialDays);

        // Buat tenant baru
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => $request->company_name,
            'invitation_code' => Str::random(10),
            'slug' => Str::slug($request->company_name . '-' . Str::random(5)),
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'address' => $request->company_address,
            'business_type' => $request->business_type,
            'is_active' => true,
            'subscription_ends_at' => config('nativephp.default_subscription_id') != null ? null : $subscriptionEndsAt,
            'pricing_plan_id' =>  config('nativephp.default_subscription_id') != null ? config('nativephp.default_subscription_id') : null,
            'is_subscribed' => true,
        ]);

        // Buat user admin (pemilik bisnis)
        $user = User::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('tenant.dashboard', ['tenantSlug' => $tenant->slug]);
    }
}
