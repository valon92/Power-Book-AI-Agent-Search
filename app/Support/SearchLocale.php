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
        return self::isAlbanian($locale) ? 'Albanian' : 'English';
    }

    public static function normalize(?string $locale): string
    {
        return self::isAlbanian($locale) ? 'sq' : 'en';
    }
}
