<?php

namespace App\Support;

/**
 * Maps visitor country (IP) → regional UI locale. English is always available.
 */
class LocaleCatalog
{
    /** UI message packs available under resources/js/i18n/locales/ */
    private const UI_PACKS = ['en', 'sq', 'de', 'fr', 'it', 'es', 'zh'];

    /** @var array<string, string> ISO 3166-1 alpha-2 → locale code */
    private const COUNTRY_TO_LOCALE = [
        // Albanian
        'XK' => 'sq', 'AL' => 'sq', 'MK' => 'sq', 'ME' => 'sq',
        // German
        'DE' => 'de', 'AT' => 'de', 'CH' => 'de', 'LI' => 'de', 'LU' => 'de',
        // French
        'FR' => 'fr', 'BE' => 'fr', 'MC' => 'fr',
        // Italian
        'IT' => 'it', 'SM' => 'it', 'VA' => 'it',
        // Spanish
        'ES' => 'es', 'AD' => 'es', 'MX' => 'es', 'AR' => 'es', 'CO' => 'es',
        'CL' => 'es', 'PE' => 'es', 'VE' => 'es', 'EC' => 'es', 'UY' => 'es',
        // Chinese
        'CN' => 'zh', 'TW' => 'zh', 'HK' => 'zh', 'MO' => 'zh', 'SG' => 'zh',
        // English-primary (regional still EN — single EN button)
        'US' => 'en', 'GB' => 'en', 'IE' => 'en', 'AU' => 'en', 'CA' => 'en',
        'NZ' => 'en', 'ZA' => 'en', 'IN' => 'en', 'PH' => 'en', 'NG' => 'en',
        'NL' => 'en', 'SE' => 'en', 'NO' => 'en', 'DK' => 'en', 'FI' => 'en',
        'PL' => 'en', 'CZ' => 'en', 'RO' => 'en', 'BG' => 'en', 'GR' => 'en',
        'PT' => 'en', 'HR' => 'en', 'RS' => 'en', 'BA' => 'en', 'SI' => 'en',
        'JP' => 'en', 'KR' => 'en', 'TR' => 'en', 'RU' => 'en', 'UA' => 'en',
    ];

    /** @var array<string, string> locale code → short header label */
    private const LOCALE_LABELS = [
        'en' => 'EN',
        'sq' => 'SQ',
        'de' => 'DE',
        'fr' => 'FR',
        'it' => 'IT',
        'es' => 'ES',
        'zh' => '中文',
    ];

    public static function localeForCountry(string $countryCode): string
    {
        $code = strtoupper($countryCode);
        $locale = self::COUNTRY_TO_LOCALE[$code] ?? 'en';

        return self::hasUiPack($locale) ? $locale : 'en';
    }

    public static function hasUiPack(string $locale): bool
    {
        return in_array(strtolower($locale), self::UI_PACKS, true);
    }

    public static function label(string $locale): string
    {
        $locale = strtolower($locale);

        return self::LOCALE_LABELS[$locale] ?? strtoupper($locale);
    }

    /**
     * Language pair for the header: EN + regional (from IP), when regional ≠ EN.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public static function uiLocalesForCountry(string $countryCode): array
    {
        $regional = self::localeForCountry($countryCode);
        $options = [['code' => 'en', 'label' => 'EN']];

        if ($regional !== 'en') {
            $options[] = [
                'code' => $regional,
                'label' => self::label($regional),
            ];
        }

        return $options;
    }

    /**
     * Default UI locale: regional language when available, otherwise English.
     */
    public static function defaultUiLocale(string $countryCode): string
    {
        $regional = self::localeForCountry($countryCode);

        return $regional !== 'en' ? $regional : 'en';
    }

    /** @return array<int, string> */
    public static function uiPackCodes(): array
    {
        return self::UI_PACKS;
    }
}
