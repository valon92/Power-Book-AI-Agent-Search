<?php

namespace App\Services\Search;

/**
 * Builds location-aware search tiers: city → country → region → international.
 */
class LocalSearchTierService
{
    /**
     * @param  array<string, mixed>  $geo
     * @return array<int, array<string, string>>
     */
    public function tiers(array $geo): array
    {
        $city = $geo['city'] ?? null;
        $country = $geo['country'] ?? 'Kosovo';
        $code = $geo['country_code'] ?? 'XK';

        $tiers = [];

        if ($city) {
            $tiers[] = [
                'level' => 'city',
                'label' => $city,
                'suffix' => $city,
            ];
        }

        $tiers[] = [
            'level' => 'country',
            'label' => $country,
            'suffix' => $country,
        ];

        foreach ($this->nearbyCountries($code) as $near) {
            $tiers[] = [
                'level' => 'region',
                'label' => $near,
                'suffix' => $near,
            ];
        }

        $tiers[] = [
            'level' => 'international',
            'label' => 'International',
            'suffix' => '',
        ];

        return $tiers;
    }

    /**
     * @param  array<string, mixed>  $geo
     * @return array<int, array<string, string>>
     */
    public function tiersForScope(array $geo, string $scope = 'auto'): array
    {
        $all = $this->tiers($geo);
        $scope = strtolower($scope);

        if ($scope === 'auto' || $scope === '') {
            return $all;
        }

        if (in_array($scope, ['world', 'universal', 'global'], true)) {
            foreach ($all as $tier) {
                if ($tier['level'] === 'international') {
                    return [$tier];
                }
            }

            return [
                ['level' => 'international', 'label' => 'International', 'suffix' => ''],
            ];
        }

        $levels = match ($scope) {
            'city', 'local' => ['city'],
            'country' => ['city', 'country'],
            'region' => ['city', 'country', 'region'],
            default => null,
        };

        if ($levels === null) {
            return $all;
        }

        $filtered = array_values(array_filter(
            $all,
            fn (array $tier) => in_array($tier['level'], $levels, true)
        ));

        if ($scope === 'city' && $filtered === []) {
            return array_values(array_filter($all, fn ($t) => $t['level'] === 'country'));
        }

        return $filtered !== [] ? $filtered : $all;
    }

    /**
     * @return array<int, string>
     */
    private function nearbyCountries(string $code): array
    {
        return match (strtoupper($code)) {
            'XK' => ['Albania', 'North Macedonia', 'Germany'],
            'AL' => ['Kosovo', 'Italy', 'Greece'],
            'DE' => ['Austria', 'Netherlands', 'France'],
            default => ['Germany', 'United Kingdom', 'United States'],
        };
    }
}
