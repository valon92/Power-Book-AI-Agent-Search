<?php

namespace App\Services\Marketplace;

/**
 * Builds a single search query string from AI-parsed attributes.
 */
class MarketplaceQueryBuilder
{
    /**
     * @param  array<string, mixed>  $parsedQuery
     * @param  array<string, mixed>  $geo
     * @param  string|null  $locationSuffix  City/country suffix for local-first search
     */
    public function build(array $parsedQuery, array $geo = [], ?string $locationSuffix = null): string
    {
        if (! empty($parsedQuery['search_query'])) {
            $base = $parsedQuery['search_query'];
        } else {
            $parts = array_filter([
                $parsedQuery['brand'] ?? null,
                $parsedQuery['model'] ?? null,
                $parsedQuery['genre'] ?? null,
                $parsedQuery['product_type'] ?? null,
                $parsedQuery['color'] ?? null,
                $parsedQuery['style'] ?? null,
                isset($parsedQuery['year']) ? (string) $parsedQuery['year'] : null,
            ]);

            if (! empty($parsedQuery['raw_query'])) {
                $parts[] = $parsedQuery['raw_query'];
            }

            if (! empty($parsedQuery['description'])) {
                $parts[] = $parsedQuery['description'];
            }

            $base = trim(implode(' ', array_unique($parts)));

            if ($base === '') {
                $keywords = $parsedQuery['keywords'] ?? [];
                $base = is_array($keywords) && count($keywords)
                    ? implode(' ', $keywords)
                    : 'products';
            }
        }

        if ($locationSuffix) {
            $base = trim($base.' '.$locationSuffix);
        }

        return $base;
    }
}
