<?php

namespace App\Services\Geo;

use App\Support\LocaleCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves visitor country/region via free IP geolocation APIs.
 */
class GeoLocationService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(?string $ip = null): array
    {
        $ip = $ip ?: request()->ip();

        if ($this->isLocalIp($ip)) {
            return $this->fallback();
        }

        $ipApi = $this->fetchFromIpApi($ip);
        if ($ipApi) {
            return $ipApi;
        }

        $ipApiCo = $this->fetchFromIpApiCo($ip);
        if ($ipApiCo) {
            return $ipApiCo;
        }

        return $this->fallback();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchFromIpApi(string $ip): ?array
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,regionName,city,lat,lon,query',
            ]);

            if (! $response->successful() || ($response->json('status') !== 'success')) {
                return null;
            }

            $code = $response->json('countryCode', 'US');

            return $this->geoPayload($ip, $code, $response->json('country', 'Unknown'), $response->json('regionName'), $response->json('city'), $response->json('lat'), $response->json('lon'), 'ip-api.com');
        } catch (\Throwable $e) {
            Log::debug('ip-api.com geolocation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchFromIpApiCo(string $ip): ?array
    {
        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if (! $response->successful() || $response->json('error')) {
                return null;
            }

            $code = $response->json('country_code', 'US');

            return $this->geoPayload($ip, $code, $response->json('country_name', 'Unknown'), $response->json('region'), $response->json('city'), $response->json('latitude'), $response->json('longitude'), 'ipapi.co');
        } catch (\Throwable $e) {
            Log::debug('ipapi.co geolocation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function localeForCountry(string $countryCode): string
    {
        return LocaleCatalog::localeForCountry($countryCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function geoPayload(
        string $ip,
        string $countryCode,
        ?string $country,
        ?string $region,
        ?string $city,
        ?float $latitude,
        ?float $longitude,
        string $provider,
    ): array {
        $code = strtoupper($countryCode);
        $regional = LocaleCatalog::localeForCountry($code);

        return [
            'ip' => $ip,
            'country' => $country,
            'country_code' => $code,
            'region' => $region,
            'city' => $city,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'locale' => $regional,
            'regional_locale' => $regional,
            'locale_label' => LocaleCatalog::label($regional),
            'default_locale' => LocaleCatalog::defaultUiLocale($code),
            'ui_locales' => LocaleCatalog::uiLocalesForCountry($code),
            'provider' => $provider,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallback(): array
    {
        $city = env('POWERBOOK_DEFAULT_CITY', 'Ferizaj');

        return $this->geoPayload(
            request()->ip() ?? '127.0.0.1',
            'XK',
            'Kosovo',
            'Pristina',
            $city,
            42.6629,
            21.1655,
            'fallback'
        );
    }

    private function isLocalIp(?string $ip): bool
    {
        return ! $ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.');
    }
}
