<?php

namespace App\Support;

/**
 * Locale helpers for AI prompts (UI language vs marketplace search language).
 */
class SearchLocale
{
    public static function isAlbanian(?string $locale): bool
    {
        return in_array(strtolower((string) $locale), ['sq', 'al'], true);
    }

    public static function descriptionLanguage(?string $locale): string
    {
        return match (strtolower((string) $locale)) {
            'sq', 'al' => 'Albanian',
            'de' => 'German',
            'fr' => 'French',
            'it' => 'Italian',
            'es' => 'Spanish',
            'zh' => 'Chinese',
            default => 'English',
        };
    }

    public static function normalize(?string $locale): string
    {
        $code = strtolower((string) $locale);

        return in_array($code, ['sq', 'de', 'fr', 'it', 'es', 'zh'], true) ? $code : 'en';
    }
}
