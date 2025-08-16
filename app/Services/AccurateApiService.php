<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AccurateApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.accurate.client_id');
        $this->clientSecret = config('services.accurate.client_secret');
        $this->redirectUri = config('services.accurate.redirect_uri');
    }

    public function getAuthUrl()
    {
        $url = 'https://account.accurate.id/oauth2/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => 'accurate-api',
        ]);
        Log::info('[AccurateAPI] getAuthUrl', ['url' => $url]);
        return $url;
    }

    public function exchangeCodeForToken($code)
    {
        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];
        $authHeader = 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);

        Log::info('[AccurateAPI] exchangeCodeForToken - Request', [
            'payload' => $payload,
            'authHeader' => $authHeader,
        ]);

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
        ])->asForm()->post('https://account.accurate.id/oauth/token', $payload);

        Log::info('[AccurateAPI] exchangeCodeForToken - Response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
        return $response->json();
    }

     public function getDbList($tenantSlug)
    {
        $token = $this->getTokenForTenant($tenantSlug);
        $url = 'https://account.accurate.id/api/db-list.do';
        $method = 'GET';

        // Log request
        \Log::info('Accurate API Request', [
            'url' => $url,
            'method' => $method,
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'payload' => null,
        ]);

        $response = Http::withToken($token)
            ->get($url);

        // Log response
        \Log::info('Accurate API Response', [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response->json('d') ?? [];
    }

    public function openDb($tenantSlug, $dbId)
    {
        $token = $this->getTokenForTenant($tenantSlug);
        $url = 'https://account.accurate.id/api/open-db.do';
        $method = 'GET';
        $payload = ['id' => $dbId];

        // Log request
        \Log::info('Accurate API Request', [
            'url' => $url,
            'method' => $method,
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'payload' => $payload,
        ]);

        $response = Http::withToken($token)
            ->get($url, $payload);

        // Log response
        \Log::info('Accurate API Response', [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response->json('session') ?? '';
    }

    public function checkSession($tenantSlug, $session)
    {
        $token = $this->getTokenForTenant($tenantSlug);
        $url = 'https://account.accurate.id/api/db-check-session.do';
        $method = 'GET';
        $payload = ['session' => $session];

        // Log request
        \Log::info('Accurate API Request', [
            'url' => $url,
            'method' => $method,
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'payload' => $payload,
        ]);

        $response = Http::withToken($token)
            ->get($url, $payload);

        // Log response
        \Log::info('Accurate API Response', [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        // Use the 'd' field from Accurate response as session validity
        $body = $response->json();
        return isset($body['d']) ? (bool)$body['d'] : false;
    }

    public function getTokenForTenant($tenantSlug)
    {
        // Example: fetch token from Tenant model by slug
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->first();
        return $tenant ? $tenant->accurate_access_token : null;
    }
}