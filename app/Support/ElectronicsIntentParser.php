<?php

namespace App\Support;

/**
 * Rule-based electronics intent from Albanian/English queries.
 */
class ElectronicsIntentParser
{
    /** @var array<string, string> */
    private const COLORS = [
        'e zezë' => 'black',
        'e zeze' => 'black',
        'zezë' => 'black',
        'zeze' => 'black',
        'zez' => 'black',
        'black' => 'black',
        'e bardhë' => 'white',
        'e bardhe' => 'white',
        'bardhë' => 'white',
        'bardhe' => 'white',
        'white' => 'white',
        'blue' => 'blue',
        'gold' => 'gold',
        'silver' => 'silver',
        'purple' => 'purple',
        'green' => 'green',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function fromQuery(string $query): array
    {
        $lower = mb_strtolower(trim($query));
        $result = [];

        if (preg_match('/\biphone\s*(\d+\s*(?:pro\s*)?(?:max)?(?:\s*pro)?(?:\s*max)?)/iu', $lower, $m)) {
            $result['brand'] = 'apple';
            $result['product_type'] = 'phone';
            $result['model'] = trim(preg_replace('/\s+/', ' ', $m[0]));
        } elseif (preg_match('/\biphone\b/u', $lower)) {
            $result['brand'] = 'apple';
            $result['product_type'] = 'phone';
        } elseif (preg_match('/\bsamsung\s+galaxy\s*(s\d+\s*(?:ultra|plus|fe)?|a\d+)/iu', $lower, $m)) {
            $result['brand'] = 'samsung';
            $result['product_type'] = 'phone';
            $result['model'] = trim($m[0]);
        } elseif (preg_match('/\b(macbook|ipad|airpods)\b/u', $lower, $m)) {
            $result['brand'] = 'apple';
            $result['product_type'] = match ($m[1]) {
                'macbook' => 'laptop',
                'ipad' => 'tablet',
                default => 'headphones',
            };
        }

        $storage = self::extractStorage($query);
        if ($storage !== null) {
            $result['storage'] = $storage;
        }

        foreach (self::COLORS as $needle => $canonical) {
            if (str_contains($lower, $needle)) {
                $result['color'] = $canonical;
                break;
            }
        }

        return $result;
    }

    public static function extractStorage(string $query): ?string
    {
        if (! preg_match('/(\d+)\s*gb\b/i', $query, $m)) {
            return null;
        }

        $gb = (int) $m[1];

        if ($gb >= 120 && $gb <= 127) {
            $gb = 128;
        } elseif ($gb >= 250 && $gb <= 257) {
            $gb = 256;
        } elseif ($gb >= 500 && $gb <= 520) {
            $gb = 512;
        }

        return $gb >= 1024 ? '1TB' : $gb.'GB';
    }

    public static function isElectronicsQuery(string $query): bool
    {
        return self::fromQuery($query) !== [];
    }
}
