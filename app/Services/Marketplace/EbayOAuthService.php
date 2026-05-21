<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * eBay OAuth2 client credentials token (cached).
 */
class EbayOAuthService
{
    public function getAccessToken(): string
    {
        $clientId = config('ebay.client_id');
        $clientSecret = config('ebay.client_secret');

        if (! $clientId || ! $clientSecret) {
            throw new \RuntimeException('EBAY_CLIENT_ID and EBAY_CLIENT_SECRET are required.');
        }

        return Cache::remember('ebay_oauth_token', 7000, function () use ($clientId, $clientSecret) {
            $base = config('ebay.sandbox')
                ? 'https://api.sandbox.ebay.com'
                : 'https://api.ebay.com';

            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->timeout(config('ebay.timeout', 15))
                ->post("{$base}/identity/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                    'scope' => 'https://api.ebay.com/oauth/api_scope',
                ]);

            if (! $response->successful()) {
                Log::warning('eBay OAuth failed', ['status' => $response->status(), 'body' => $response->json()]);
                throw new \RuntimeException('eBay OAuth failed: '.$response->status());
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new \RuntimeException('eBay OAuth returned empty token.');
            }

            return $token;
        });
    }

    public function isConfigured(): bool
    {
        return config('ebay.enabled')
            && config('ebay.client_id')
            && config('ebay.client_secret');
    }
}
