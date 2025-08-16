<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\AccurateApiService;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class AccurateController extends Controller
{
    public function redirectToAccurate($tenantSlug)
    {
        // Konfigurasi sesuai aplikasi Anda
        $clientId = config('services.accurate.client_id');
        $redirectUri = config('services.accurate.redirect_uri');
        $scope = 'item_view item_save sales_invoice_view'; // sesuaikan kebutuhan
        $authorizeUrl = 'https://account.accurate.id/oauth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
        ]);
        return redirect($authorizeUrl);
    }

    // Show Accurate Online login page
    public function showLogin(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id) {
            return redirect()->route('dashboard.default')->with('error', 'Tenant tidak ditemukan.');
        }

        $tenant = \App\Models\Tenant::find($user->tenant_id);
        if (!$tenant) {
            return redirect()->route('dashboard.default')->with('error', 'Tenant tidak ditemukan.');
        }

        $isIntegrated = !empty($tenant->accurate_access_token);
        $tokenValid = false;
        if ($isIntegrated) {
            $tokenValid = $tenant->accurate_token_expires_at
                ? now()->lt($tenant->accurate_token_expires_at)
                : true;
        }

        $redirectUri = config('services.accurate.redirect_uri');
        return \Inertia\Inertia::render('Accurate/Login', [
            'tenantSlug' => $tenant->slug,
            'tenantName' => $tenant->name,
            'isIntegrated' => $isIntegrated,
            'tokenValid' => $tokenValid,
            'success' => session('success'),
            'redirectUri' => $redirectUri,
            // ...existing props...
        ]);
    }

    // Handle Accurate Online login form submission
    public function login(Request $request, $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Cek apakah sudah terintegrasi
        $isIntegrated = !empty($tenant->accurate_access_token);

        // Cek token valid (misal: ada expired_at dan belum expired)
        $tokenValid = false;
        if ($isIntegrated) {
            // Contoh: cek expired_at, sesuaikan field sesuai implementasi Anda
            $tokenValid = $tenant->accurate_token_expired_at
                ? now()->lt($tenant->accurate_token_expired_at)
                : true;
        }

        return inertia('Accurate/Login', [
            'tenantSlug' => $tenant->slug,
            'tenantName' => $tenant->name,
            'isIntegrated' => $isIntegrated,
            'tokenValid' => $tokenValid,
            'success' => session('success'),
            // ...existing props...
        ]);
    }

    // Callback Accurate tanpa tenantSlug di URL
    public function handleCallbackGlobal(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id) {
            return redirect()->route('dashboard.default')->with('error', 'Tenant tidak ditemukan.');
        }

        $tenant = \App\Models\Tenant::find($user->tenant_id);
        if (!$tenant) {
            return redirect()->route('dashboard.default')->with('error', 'Tenant tidak ditemukan.');
        }

        $service = new \App\Services\AccurateApiService();
        $code = $request->input('code');
        $tokenData = $service->exchangeCodeForToken($code);

        $tenant->accurate_access_token = $tokenData['access_token'] ?? null;
        $tenant->accurate_refresh_token = $tokenData['refresh_token'] ?? null;
        $tenant->accurate_token_expires_at = isset($tokenData['expires_in']) ? \Carbon\Carbon::now()->addSeconds($tokenData['expires_in']) : null;
        $tenant->save();

        // Set isIntegrated and tokenValid after saving token
        $isIntegrated = !empty($tenant->accurate_access_token);
        $tokenValid = false;
        if ($isIntegrated) {
            $tokenValid = $tenant->accurate_token_expires_at
                ? now()->lt($tenant->accurate_token_expires_at)
                : true;
        }

        return inertia('Accurate/Login', [
            'tenantSlug' => $tenant->slug,
            'tenantName' => $tenant->name,
            'isIntegrated' => $isIntegrated,
            'tokenValid' => $tokenValid,
            'success' => session('success'),
        ]);
    }

    // Refresh Accurate Online access token for a tenant
    public function refreshAccurateToken($tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $refreshToken = $tenant->accurate_refresh_token;

        if (!$refreshToken) {
            return redirect()->back()->with('error', 'Refresh token tidak tersedia.');
        }

        $clientId = config('services.accurate.client_id');
        $clientSecret = config('services.accurate.client_secret');
        $tokenUrl = 'https://account.accurate.id/oauth/token';

        $authHeader = 'Basic ' . base64_encode($clientId . ':' . $clientSecret);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $authHeader,
        ])->asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            $tokenData = $response->json();
            $tenant->accurate_access_token = $tokenData['access_token'] ?? null;
            $tenant->accurate_refresh_token = $tokenData['refresh_token'] ?? null;
            $tenant->accurate_token_expires_at = isset($tokenData['expires_in']) ? Carbon::now()->addSeconds($tokenData['expires_in']) : null;
            $tenant->save();

            return redirect()->back()->with('success', 'Access token berhasil di-refresh!');
        } else {
            return redirect()->back()->with('error', 'Gagal refresh token: ' . $response->body());
        }
    }

    public function dbList(Request $request)
    {
        $tenantSlug = $request->get('tenantSlug');
        Log::info('[AccurateAPI] dbList request', ['tenantSlug' => $tenantSlug]);
        $result = app(AccurateApiService::class)->getDbList($tenantSlug);
        Log::info('[AccurateAPI] dbList response', ['result' => $result]);
        return response()->json(['dbList' => $result]);
    }

    public function openDb(Request $request)
    {
        $tenantSlug = $request->get('tenantSlug');
        $dbId = $request->get('id');
        Log::info('[AccurateAPI] openDb request', ['tenantSlug' => $tenantSlug, 'dbId' => $dbId]);
        $session = app(AccurateApiService::class)->openDb($tenantSlug, $dbId);
        Log::info('[AccurateAPI] openDb response', ['session' => $session]);
        return response()->json(['session' => $session]);
    }

    public function dbCheckSession(Request $request)
    {
        $tenantSlug = $request->get('tenantSlug');
        $session = $request->get('session');
        Log::info('[AccurateAPI] dbCheckSession request', ['tenantSlug' => $tenantSlug, 'session' => $session]);
        $valid = app(AccurateApiService::class)->checkSession($tenantSlug, $session);
        Log::info('[AccurateAPI] dbCheckSession response', ['valid' => $valid]);
        return response()->json(['valid' => $valid]);
    }

    public function syncMaster(Request $request)
    {
        $tenantSlug = $request->input('tenantSlug');
        $session = $request->input('session');
        $dbId = $request->input('dbId');
        $type = $request->input('type'); // customer, item, vendor, etc

        $service = new \App\Services\AccurateApiService();
        $result = $service->syncMaster($tenantSlug, $session, $dbId, $type);

        return response()->json([
            'success' => $result,
            'type' => $type,
        ]);
    }
}