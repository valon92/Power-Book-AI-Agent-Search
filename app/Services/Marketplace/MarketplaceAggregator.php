<?php

namespace App\Services\Marketplace;

use App\Support\CategoryCatalog;
use App\Support\SwissCarMarketplaces;

/**
 * Internet search: live APIs with local-first location tiers, then demo fallbacks.
 */
class MarketplaceAggregator
{
    /** @var array<int, string> */
    private array $allSources = [
        'ebay',
        'google_shopping',
        'amazon',
        'mobile.de',
        'autoscout24',
        'etsy',
        'facebook_marketplace',
    ];

    public function __construct(
        private EbayBrowseService $ebayBrowse,
        private EbayOAuthService $ebayOAuth,
        private SerpApiShoppingService $serpApi,
    ) {}

    /**
     * @param  array<string, mixed>  $parsedQuery
     * @param  array<string, mixed>  $expandedFilters
     * @param  array<string, mixed>  $geo
     * @return array{results: array<int, array<string, mixed>>, report: array<int, array<string, mixed>>}
     */
    public function searchAll(array $parsedQuery, array $expandedFilters, array $geo = []): array
    {
        $targetMarketplaces = $expandedFilters['marketplaces'] ?? $this->allSources;
        $tiers = $expandedFilters['location_tiers'] ?? [['suffix' => '', 'label' => 'International', 'level' => 'international']];
        $results = [];
        $report = [];
        $liveResultCount = 0;

        $countryCode = strtoupper((string) ($parsedQuery['search_country_code'] ?? $geo['country_code'] ?? ''));
        $category = CategoryCatalog::normalize($parsedQuery['category'] ?? 'marketplace');
        $searchCountry = $parsedQuery['search_country'] ?? $geo['country'] ?? '';
        $swissCarSearch = $countryCode === 'CH' && CategoryCatalog::isAutomotive($category);

        $liveSources = [
            ['key' => 'ebay', 'provider' => $this->ebayBrowse, 'active' => $this->ebayOAuth->isConfigured()],
            ['key' => 'google_shopping', 'provider' => $this->serpApi, 'active' => $this->serpApi->isConfigured()],
        ];

        foreach ($liveSources as $live) {
            if ($swissCarSearch) {
                $report[] = $this->reportRow($live['key'], 'skipped', 0, 'swiss_car_marketplaces', $searchCountry);

                continue;
            }

            if (! $live['active'] || ! $this->shouldQuerySource($live['key'], $targetMarketplaces, $category)) {
                $report[] = $this->reportRow($live['key'], 'skipped', 0, 'not_configured', '');

                continue;
            }

            $tierHits = 0;
            foreach ($tiers as $tier) {
                if ($liveResultCount >= 16) {
                    break 2;
                }

                $expandedFilters['location_suffix'] = $tier['suffix'] ?? '';
                $items = $live['provider']->search($parsedQuery, $expandedFilters);
                $liveResultCount += count($items);
                $results = array_merge($results, $items);
                $tierHits += count($items);

                if (count($items) >= 6) {
                    break;
                }
            }

            $report[] = $this->reportRow(
                $live['key'],
                'live',
                $tierHits,
                'ok',
                $tiers[0]['label'] ?? 'local'
            );
        }

        if ($countryCode === 'XK' && (CategoryCatalog::isLocalFashion($category) || $category === 'marketplace')) {
            $driloni = new MockMarketplaceService('driloni');
            $expandedFilters['location_suffix'] = $geo['city'] ?? 'Kosovo';
            $driloniItems = $driloni->search($parsedQuery, $expandedFilters);
            $results = array_merge($results, $driloniItems);
            $report[] = $this->reportRow('driloni', 'live', count($driloniItems), 'local_store', $geo['city'] ?? 'Kosovo');
        }

        $skipMocks = ! $swissCarSearch && $liveResultCount >= 8;
        $mockSources = $swissCarSearch
            ? SwissCarMarketplaces::keys()
            : ['amazon', 'mobile.de', 'autoscout24', 'etsy', 'facebook_marketplace'];

        foreach ($mockSources as $source) {
            if ($skipMocks) {
                $report[] = $this->reportRow($source, 'skipped', 0, 'live_results_sufficient', '');

                continue;
            }

            if (! $this->shouldQuerySource($source, $targetMarketplaces, $category)) {
                continue;
            }

            $mock = new MockMarketplaceService($source);
            $expandedFilters['location_suffix'] = $searchCountry ?: ($geo['city'] ?? $geo['country'] ?? '');
            $items = $mock->search($parsedQuery, $expandedFilters);
            $results = array_merge($results, $items);
            $report[] = $this->reportRow(
                $source,
                'demo',
                count($items),
                $swissCarSearch ? 'swiss_car_marketplace' : 'mock_data',
                $searchCountry ?: ($geo['city'] ?? '')
            );
        }

        return [
            'results' => $this->deduplicate($results),
            'report' => $report,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    private function deduplicate(array $results): array
    {
        $seen = [];

        return array_values(array_filter($results, function ($item) use (&$seen) {
            $key = ($item['id'] ?? '').'-'.($item['source_key'] ?? '');
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;

            return true;
        }));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportRow(string $source, string $mode, int $count, string $status, string $location): array
    {
        return [
            'source' => $source,
            'label' => SwissCarMarketplaces::label($source),
            'mode' => $mode,
            'count' => $count,
            'status' => $status,
            'location' => $location,
        ];
    }

    /**
     * @param  array<int, string>  $targetMarketplaces
     */
    private function shouldQuerySource(string $source, array $targetMarketplaces, string $category): bool
    {
        if (empty($targetMarketplaces)) {
            return true;
        }

        if (SwissCarMarketplaces::isTarget($source, $targetMarketplaces)) {
            return true;
        }

        $sourceNorm = strtolower(str_replace(['.', '_'], '', $source));

        foreach ($targetMarketplaces as $target) {
            $targetNorm = strtolower(str_replace(['.', '_', ' '], '', $target));
            if (str_contains($sourceNorm, $targetNorm) || str_contains($targetNorm, $sourceNorm)) {
                return true;
            }
        }

        return match (CategoryCatalog::normalize($category)) {
            'automotive' => in_array($source, ['mobile.de', 'autoscout24', 'ebay', 'google_shopping'], true),
            'electronics_tech', 'gaming_entertainment', 'home_appliances' => in_array($source, ['amazon', 'ebay', 'google_shopping'], true),
            'fashion', 'sports_outdoor', 'luxury_collectibles' => in_array($source, ['driloni', 'ebay', 'google_shopping', 'etsy', 'facebook_marketplace'], true),
            default => in_array($source, ['ebay', 'amazon', 'google_shopping', 'etsy'], true),
        };
    }
}
