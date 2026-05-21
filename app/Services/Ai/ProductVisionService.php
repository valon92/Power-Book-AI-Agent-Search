<?php

namespace App\Services\Ai;

use App\Support\SearchLocale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes product photos via OpenAI Vision (gpt-4o-mini).
 */
class ProductVisionService
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(
        string $imageBase64,
        ?string $userHint = null,
        ?array $geo = null,
        ?string $locale = 'en',
    ): array {
        $apiKey = config('openai.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY required for image search.');
        }

        $mime = $this->detectMime($imageBase64);
        $dataUri = "data:{$mime};base64,{$imageBase64}";

        $location = '';
        if ($geo) {
            $location = trim(($geo['city'] ?? '').', '.($geo['country'] ?? ''));
        }

        $hint = $userHint ? "User note: {$userHint}" : 'No extra text from user.';
        $locCtx = $location ? "Buyer location: {$location}. Prefer local/regional availability." : '';
        $lang = SearchLocale::descriptionLanguage($locale);
        $locale = SearchLocale::normalize($locale);

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('openai.vision_model', 'gpt-4o-mini'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are Powerbook.ai visual product expert. Analyze shopping product photos and return JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => <<<PROMPT
Analyze this product image for a semantic shopping search engine.
{$locCtx}
{$hint}

Return JSON:
{
  "description": "detailed product description for the buyer UI in {$lang}",
  "search_query": "optimized short search phrase in English for eBay/Google (brand, model, color, product type)",
  "category": "car|book|painting|electronics|furniture|fashion|luxury|collectibles|gift|marketplace",
  "brand": null,
  "model": null,
  "color": null,
  "style": null,
  "materials": [],
  "keywords": [],
  "condition_guess": "new|used|unknown"
}
PROMPT,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $dataUri, 'detail' => 'low'],
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI Vision failed', ['status' => $response->status()]);
            throw new \RuntimeException('Vision API error: '.$response->status());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid vision JSON response.');
        }

        $decoded['vision'] = true;
        $decoded['parser'] = 'openai-vision';
        $decoded['locale'] = $locale;

        return $decoded;
    }

    private function detectMime(string $base64): string
    {
        $bin = base64_decode(substr($base64, 0, 32), true);
        if ($bin === false) {
            return 'image/jpeg';
        }
        if (str_starts_with($bin, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($bin, 'RIFF') && str_contains(substr($bin, 0, 12), 'WEBP')) {
            return 'image/webp';
        }

        return 'image/jpeg';
    }
}
