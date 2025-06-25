<?php

namespace App\Helpers;

use Sokil\IsoCodes\IsoCodesFactory;
use Illuminate\Support\Facades\DB;

class IsoCodesHelper
{
    private static $fallbackTranslations = [
        'CU' => 'церковнославянский',
        'DV' => 'мальдивский',
        'KL' => 'гренландский',
        'EL' => 'греческий (с 1453)',
        'NE' => 'непальский',
        'ND' => 'северный ндебеле',
        'NY' => 'ньянджа',
        'OC' => 'окситанский (после 1500)',
        'OR' => 'ория',
        'GD' => 'гэльский (шотландский)',
        'SH' => 'сербскохорватский',
        'II' => 'сычуаньский',
        'NR' => 'южный ндебеле',
        'ST' => 'сото южный',
        'FY' => 'фризский западный',
        'ZA' => 'чжуанский',
        'KM' => 'кхмерский',
        'LI' => 'лимбургский',
        'MH' => 'маршалльский',
        'NV' => 'навахо',
        'NB' => 'норвежский букмол',
        'OS' => 'осетинский',
        'PA' => 'панджаби',
        'RM' => 'романшский',
        'SE' => 'северносаамский',
        'SI' => 'сингальский',
        'TH' => 'тайский',
        'TI' => 'тигринья',
        'TK' => 'туркменский',
        'TL' => 'тагальский',
        'TN' => 'тсвана',
        'TO' => 'тонганский',
        'TS' => 'тсонга',
        'UG' => 'уйгурский',
        'VE' => 'венда',
        'VO' => 'волапюк',
        'WA' => 'валлонский',
        'WO' => 'волоф',
        'XH' => 'коса',
        'YO' => 'йоруба',
    ];

    /**
     * Получить данные стран и языков для указанной локали
     * 
     * @param string $locale Локаль (например, 'ru_RU.UTF-8')
     * @return array Массив с данными стран и языков
     */
    public static function getCountryAndLanguageDataByLocale(string $locale): array
    {
        self::setLocale($locale);

        $isoCodes = new IsoCodesFactory();

        return [
            'countries' => self::getCountriesData($isoCodes),
            'languages' => self::getLanguagesData($isoCodes)
        ];
    }

    /**
     * Получить данные всех стран
     * 
     * @param IsoCodesFactory $isoCodes
     * @return array
     */
    private static function getCountriesData(IsoCodesFactory $isoCodes): array
    {
        $countries = $isoCodes->getCountries();
        $countriesData = [];

        foreach ($countries as $country) {
            $alpha2 = $country->getAlpha2();

            // Пропускаем страны без ISO2 кода
            if (empty($alpha2)) {
                continue;
            }

            $countriesData[] = [
                'iso2' => strtoupper($alpha2),
                'iso3' => strtoupper($country->getAlpha3()),
                'numeric_code' => $country->getNumericCode(),
                'name' => $country->getLocalName(),
            ];
        }

        return $countriesData;
    }

    /**
     * Получить данные всех языков
     * 
     * @param IsoCodesFactory $isoCodes
     * @return array
     */
    private static function getLanguagesData(IsoCodesFactory $isoCodes): array
    {
        $languages = $isoCodes->getLanguages();
        $languagesData = [];

        foreach ($languages as $language) {
            $alpha2 = $language->getAlpha2();

            // Пропускаем языки без ISO2 кода
            if (empty($alpha2)) {
                continue;
            }

            $languagesData[] = [
                'iso2' => strtoupper($alpha2),
                'iso3' => strtoupper($language->getAlpha3()),
                'name' => $language->getLocalName(),
            ];
        }

        return $languagesData;
    }

    /**
     * Установить локаль для gettext
     * 
     * @param string $locale
     */
    private static function setLocale(string $locale): void
    {
        putenv("LANGUAGE=$locale");
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain("iso3166", "/usr/share/locale");
        bindtextdomain("iso639", "/usr/share/locale");
        textdomain("iso639");
    }

    /**
     * Получить русский перевод для языка с использованием fallback переводов
     * 
     * @param string $code ISO 639-1 код языка
     * @return string Русский перевод
     */
    public static function getRussianLanguageTranslation(string $code): string
    {
        return self::$fallbackTranslations[strtoupper($code)] ?? '';
    }

    /**
     * Проверить, есть ли fallback перевод для данного кода
     * 
     * @param string $code ISO код
     * @return bool
     */
    public static function isFallback(string $code): bool
    {
        return isset(self::$fallbackTranslations[strtoupper($code)]);
    }

    /**
     * Получить страну по ISO2 коду из локальной базы данных
     * 
     * @param string $iso2 ISO2 код страны
     * @param string $languageCode Код языка для перевода (по умолчанию 'en')
     * @return array|null
     */
    public static function getCountryFromDatabase(string $iso2, string $languageCode = 'en'): ?array
    {
        $entity = DB::table('iso_entities')
            ->where('type', 'country')
            ->where('iso_code_2', strtoupper($iso2))
            ->first();

        if (!$entity) {
            return null;
        }

        $translation = DB::table('iso_translations')
            ->where('entity_id', $entity->id)
            ->where('language_code', $languageCode)
            ->first();

        return [
            'id' => $entity->id,
            'iso2' => $entity->iso_code_2,
            'iso3' => $entity->iso_code_3,
            'numeric_code' => $entity->numeric_code,
            'name' => $translation ? $translation->translated_name : $entity->name,
            'original_name' => $entity->name,
        ];
    }

    /**
     * Получить язык по ISO2 коду из локальной базы данных
     * 
     * @param string $iso2 ISO2 код языка
     * @param string $languageCode Код языка для перевода (по умолчанию 'en')
     * @return array|null
     */
    public static function getLanguageFromDatabase(string $iso2, string $languageCode = 'en'): ?array
    {
        $entity = DB::table('iso_entities')
            ->where('type', 'language')
            ->where('iso_code_2', strtoupper($iso2))
            ->first();

        if (!$entity) {
            return null;
        }

        $translation = DB::table('iso_translations')
            ->where('entity_id', $entity->id)
            ->where('language_code', $languageCode)
            ->first();

        return [
            'id' => $entity->id,
            'iso2' => $entity->iso_code_2,
            'iso3' => $entity->iso_code_3,
            'name' => $translation ? $translation->translated_name : $entity->name,
            'original_name' => $entity->name,
        ];
    }

    /**
     * Получить все страны с переводами
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getAllCountries(string $languageCode = 'en'): array
    {
        return DB::table('iso_entities as e')
            ->leftJoin('iso_translations as t', function ($join) use ($languageCode) {
                $join->on('e.id', '=', 't.entity_id')
                    ->where('t.language_code', '=', $languageCode);
            })
            ->where('e.type', 'country')
            ->where('e.is_active', true)
            ->select([
                'e.id',
                'e.iso_code_2 as iso2',
                'e.iso_code_3 as iso3',
                'e.numeric_code',
                'e.name as original_name',
                DB::raw('COALESCE(t.translated_name, e.name) as name')
            ])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Получить все языки с переводами
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getAllLanguages(string $languageCode = 'en'): array
    {
        return DB::table('iso_entities as e')
            ->leftJoin('iso_translations as t', function ($join) use ($languageCode) {
                $join->on('e.id', '=', 't.entity_id')
                    ->where('t.language_code', '=', $languageCode);
            })
            ->where('e.type', 'language')
            ->where('e.is_active', true)
            ->select([
                'e.id',
                'e.iso_code_2 as iso2',
                'e.iso_code_3 as iso3',
                'e.name as original_name',
                DB::raw('COALESCE(t.translated_name, e.name) as name')
            ])
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
