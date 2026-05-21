<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Parses shopping queries via OpenAI Chat Completions (JSON mode).
 */
class OpenAiParserService
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $query, ?string $country = null): array
    {
        $apiKey = config('openai.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(config('openai.timeout', 20))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('openai.model', 'gpt-4o-mini'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userPrompt($query, $country)],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI parse failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            $message = match ($response->status()) {
                401 => 'Invalid OpenAI API key. Check OPENAI_API_KEY in .env (no quotes, no duplicate sk-proj- prefix).',
                429 => 'OpenAI rate limit exceeded.',
                default => 'OpenAI API error: '.$response->status(),
            };
            throw new \RuntimeException($message);
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Empty OpenAI response.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON from OpenAI.');
        }

        return $this->normalize($decoded, $query, $country);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Powerbook.ai shopping intent parser. Convert natural language product search queries into structured JSON.

Return ONLY valid JSON with this shape:
{
  "category": "car|book|painting|electronics|furniture|collectibles|fashion|real_estate|luxury|gift|marketplace",
  "keywords": ["word1", "word2"],
  "language_hint": "en|sq|de|fr|it|es",
  "brand": null,
  "model": null,
  "year": null,
  "color": null,
  "max_km": null,
  "transmission": null,
  "fuel": null,
  "genre": null,
  "product_type": null,
  "features": [],
  "max_price": null,
  "condition": null,
  "style": null,
  "room": null,
  "subject": null,
  "bedrooms": null,
  "listing_type": null
}

Rules:
- category: best match for intent
- max_km: integer kilometers for cars (180k km => 180000)
- year: integer if mentioned
- keywords: 3-8 relevant terms, lowercase
- language_hint: detect from query (Albanian => sq)
- Omit null fields or use null explicitly
- features: array of strings e.g. long_battery, quiet_cooling, gaming
PROMPT;
    }

    private function userPrompt(string $query, ?string $country): string
    {
        $ctx = $country ? "User country hint: {$country}." : '';

        return "{$ctx}\nQuery: {$query}";
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    private function normalize(array $decoded, string $query, ?string $country): array
    {
        $allowed = [
            'car', 'book', 'painting', 'electronics', 'furniture',
            'collectibles', 'fashion', 'real_estate', 'luxury', 'gift', 'marketplace',
        ];

        $category = $decoded['category'] ?? 'marketplace';
        if (! in_array($category, $allowed, true)) {
            $category = 'marketplace';
        }

        $result = [
            'raw_query' => $query,
            'category' => $category,
            'keywords' => array_values(array_filter($decoded['keywords'] ?? [], 'is_string')),
            'country' => $country,
            'language_hint' => $decoded['language_hint'] ?? 'en',
            'parser' => 'openai',
        ];

        $optional = [
            'brand', 'model', 'year', 'color', 'max_km', 'transmission', 'fuel',
            'genre', 'product_type', 'features', 'max_price', 'condition',
            'style', 'room', 'subject', 'bedrooms', 'listing_type', 'length', 'ending', 'item',
        ];

        foreach ($optional as $key) {
            if (array_key_exists($key, $decoded) && $decoded[$key] !== null && $decoded[$key] !== '') {
                $result[$key] = $decoded[$key];
            }
        }

        if (isset($result['year'])) {
            $result['year'] = (int) $result['year'];
        }
        if (isset($result['max_km'])) {
            $result['max_km'] = (int) $result['max_km'];
        }
        if (isset($result['max_price'])) {
            $result['max_price'] = (int) $result['max_price'];
        }

        return array_filter($result, fn ($v) => $v !== null && $v !== [] && $v !== '');
    }
}
