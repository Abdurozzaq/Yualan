<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant; // Import model Tenant
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str; // Untuk UUID dan slug
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register'); // Pastikan ini mengarah ke komponen Vue Anda
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi umum untuk semua tipe pendaftaran (Step 2 dari frontend)
        $request->validate([
            'registration_type' => ['required', 'string', 'in:personal,company'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $tenant = null; // Inisialisasi variabel tenant
        $user = null; // Inisialisasi variabel user

    if ($request->registration_type === 'personal') {
            // Validasi khusus untuk pendaftaran personal (Step 3 dari frontend)
            $request->validate([
                'invitation_code' => ['required', 'string', 'exists:tenants,invitation_code'],
            ]);

            // Cari tenant berdasarkan invitation_code
            $tenant = Tenant::where('invitation_code', $request->invitation_code)->first();

            if (!$tenant) {
                // Ini seharusnya sudah ditangani oleh validasi 'exists', tapi sebagai fallback
                return back()->withErrors(['invitation_code' => 'Kode undangan tidak valid.']);
            }

            // Buat user baru untuk pendaftaran personal
            $user = User::create([
                'id' => Str::uuid(), // Generate UUID untuk user
                'tenant_id' => $tenant->id, // Gunakan tenant_id dari kode undangan
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'cashier', // Default role untuk personal, bisa diubah sesuai kebutuhan
                // is_active akan default ke true, Anda mungkin perlu menambahkan kolom 'status'
                // atau 'is_approved' di tabel users untuk mekanisme persetujuan
                // atau mengelola ini di middleware/listener setelah pendaftaran.
                // Untuk saat ini, kita asumsikan user langsung aktif atau akan diatur di logic lain.
            ]);

    } elseif ($request->registration_type === 'company') {
            // Validasi khusus untuk pendaftaran perusahaan (Step 3 dari frontend)
            $request->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'company_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Tenant::class.',email'],
                'business_type' => ['required', 'string', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:255'],
                'company_address' => ['nullable', 'string'],
                'company_city' => ['nullable', 'string', 'max:255'],
                'company_state' => ['nullable', 'string', 'max:255'],
                'company_zip_code' => ['nullable', 'string', 'max:255'],
                'company_country' => ['nullable', 'string', 'max:255'],
            ]);

            // Ambil jumlah hari trial dari saas_settings
            $trialDays = 7; // Default
            $trialSetting = DB::table('saas_settings')->where('key', 'trial_days')->first();
            if ($trialSetting && is_numeric($trialSetting->value)) {
                $trialDays = (int) $trialSetting->value;
            }

            $now = now();
            $subscriptionEndsAt = $now->copy()->addDays($trialDays);

            // Buat tenant baru dengan field trial
            $tenant = Tenant::create([
                'id' => Str::uuid(), // Generate UUID untuk tenant
                'name' => $request->company_name,
                'invitation_code' => Str::random(10), // Generate invitation code unik
                'slug' => Str::slug($request->company_name . '-' . Str::random(5)), // Generate slug unik
                'email' => $request->company_email,
                'phone' => $request->company_phone,
                'address' => $request->company_address,
                'city' => $request->company_city,
                'state' => $request->company_state,
                'zip_code' => $request->zip_code, // Perbaikan: gunakan $request->zip_code
                'country' => $request->country, // Perbaikan: gunakan $request->country
                'business_type' => $request->business_type,
                'is_active' => true, // Perusahaan baru langsung aktif
                'subscription_ends_at' => config('nativephp.default_subscription_id') != null ? null : $subscriptionEndsAt,
                'pricing_plan_id' =>  config('nativephp.default_subscription_id') != null ? config('nativephp.default_subscription_id') : null,
                'is_subscribed' => true, // Trial, belum berlangganan
            ]);

            // Buat user admin untuk perusahaan baru
            $user = User::create([
                'id' => Str::uuid(), // Generate UUID untuk user
                'tenant_id' => $tenant->id, // Gunakan tenant_id yang baru dibuat
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin', // Role admin untuk user pertama perusahaan
            ]);
        } else {
            // Jika registration_type tidak valid, kembalikan error
            return back()->withErrors(['registration_type' => 'Tipe pendaftaran tidak valid.']);
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirect ke dashboard tenant yang sesuai
        if ($user->tenant_id && ($tenant = $user->tenant)) {
            return redirect()->route('tenant.dashboard', ['tenantSlug' => $tenant->slug]);
        }

        // Fallback jika entah mengapa user tidak memiliki tenant_id atau tenant tidak ditemukan
        return redirect()->route('dashboard.default');
    }
}
