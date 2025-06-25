<?php

namespace App\Helpers;

use Sokil\IsoCodes\IsoCodesFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Frontend\IsoEntity;
use App\Models\Frontend\IsoTranslation;

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
     * Массив языков/стран для исключения из обработки
     */
    private static $excludedCodes = [
        // Устаревшие или спорные коды
        'SH', // сербскохорватский (устаревший)
        'CU',
        'KL',
        "DV",
        'KM',
        'LI',
        // Добавьте сюда коды, которые нужно исключить
    ];

    /**
     * Расширенный массив русских переводов для языков
     */
    private static $extendedRussianTranslations = [
        // Языки из вашего результата запроса
        'CU' => 'церковнославянский',
        'DV' => 'мальдивский',
        'KL' => 'гренландский',
        'KM' => 'кхмерский',
        'LI' => 'лимбургский',
        'MH' => 'маршалльский',
        'EL' => 'греческий',
        'NV' => 'навахо',
        'NE' => 'непальский',
        'ND' => 'северный ндебеле',
        'SE' => 'северносаамский',
        'NB' => 'норвежский букмол',
        'NY' => 'ньянджа',
        'OC' => 'окситанский',
        'OR' => 'ория',
        'OS' => 'осетинский',
        'PA' => 'панджаби',
        'RM' => 'романшский',
        'GD' => 'шотландский гэльский',
        'SH' => 'сербскохорватский',
        'II' => 'сычуаньский йи',
        'SI' => 'сингальский',
        'NR' => 'южный ндебеле',
        'ST' => 'южный сото',
        'FY' => 'западнофризский',
        'ZA' => 'чжуанский',
        'TH' => 'тайский',
        'TI' => 'тигринья',
        'TK' => 'туркменский',
        'TL' => 'тагалог',
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

        // Дополнительные переводы
        'AB' => 'абхазский',
        'AV' => 'аварский',
        'AE' => 'авестийский',
        'AZ' => 'азербайджанский',
        'AY' => 'аймара',
        'AK' => 'акан',
        'SQ' => 'албанский',
        'AM' => 'амхарский',
        'EN' => 'английский',
        'AR' => 'арабский',
        'AN' => 'арагонский',
        'HY' => 'армянский',
        'AS' => 'ассамский',
        'AF' => 'африкаанс',
        'EU' => 'баскский',
        'BA' => 'башкирский',
        'BE' => 'белорусский',
        'BN' => 'бенгальский',
        'BG' => 'болгарский',
        'BO' => 'тибетский',
        'BS' => 'боснийский',
        'BR' => 'бретонский',
        'CA' => 'каталанский',
        'CH' => 'чаморро',
        'CE' => 'чеченский',
        'CS' => 'чешский',
        'CV' => 'чувашский',
        'CY' => 'валлийский',
        'DA' => 'датский',
        'DE' => 'немецкий',
        'DZ' => 'дзонг-кэ',
        'EE' => 'эве',
        'ES' => 'испанский',
        'ET' => 'эстонский',
        'FA' => 'персидский',
        'FI' => 'финский',
        'FJ' => 'фиджийский',
        'FO' => 'фарерский',
        'FR' => 'французский',
        'GA' => 'ирландский',
        'GL' => 'галисийский',
        'GU' => 'гуджарати',
        'GV' => 'мэнский',
        'HA' => 'хауса',
        'HE' => 'иврит',
        'HI' => 'хинди',
        'HO' => 'хири-моту',
        'HR' => 'хорватский',
        'HT' => 'гаитянский креольский',
        'HU' => 'венгерский',
        'HZ' => 'гереро',
        'IA' => 'интерлингва',
        'ID' => 'индонезийский',
        'IE' => 'интерлингве',
        'IG' => 'игбо',
        'IK' => 'инупиак',
        'IO' => 'идо',
        'IS' => 'исландский',
        'IT' => 'итальянский',
        'IU' => 'инуктитут',
        'JA' => 'японский',
        'JV' => 'яванский',
        'KA' => 'грузинский',
        'KG' => 'конго',
        'KI' => 'кикуйю',
        'KJ' => 'квайама',
        'KK' => 'казахский',
        'KN' => 'каннада',
        'KO' => 'корейский',
        'KR' => 'канури',
        'KS' => 'кашмири',
        'KU' => 'курдский',
        'KV' => 'коми',
        'KW' => 'корнский',
        'KY' => 'киргизский',
        'LA' => 'латинский',
        'LB' => 'люксембургский',
        'LG' => 'ганда',
        'LN' => 'лингала',
        'LO' => 'лаосский',
        'LT' => 'литовский',
        'LU' => 'луба-катанга',
        'LV' => 'латышский',
        'MG' => 'малагасийский',
        'MI' => 'маори',
        'MK' => 'македонский',
        'ML' => 'малаялам',
        'MN' => 'монгольский',
        'MO' => 'молдавский',
        'MR' => 'маратхи',
        'MS' => 'малайский',
        'MT' => 'мальтийский',
        'MY' => 'бирманский',
        'NA' => 'науру',
        'NN' => 'норвежский нюнорск',
        'NO' => 'норвежский',
        'OJ' => 'оджибве',
        'OM' => 'оромо',
        'PL' => 'польский',
        'PS' => 'пушту',
        'PT' => 'португальский',
        'QU' => 'кечуа',
        'RN' => 'рунди',
        'RO' => 'румынский',
        'RU' => 'русский',
        'RW' => 'киньяруанда',
        'SA' => 'санскрит',
        'SC' => 'сардинский',
        'SD' => 'синдхи',
        'SG' => 'санго',
        'SK' => 'словацкий',
        'SL' => 'словенский',
        'SM' => 'самоанский',
        'SN' => 'шона',
        'SO' => 'сомали',
        'SR' => 'сербский',
        'SS' => 'свати',
        'SU' => 'сунданский',
        'SV' => 'шведский',
        'SW' => 'суахили',
        'TA' => 'тамильский',
        'TE' => 'телугу',
        'TG' => 'таджикский',
        'TR' => 'турецкий',
        'TT' => 'татарский',
        'TW' => 'тви',
        'TY' => 'таитянский',
        'UK' => 'украинский',
        'UR' => 'урду',
        'UZ' => 'узбекский',
        'VI' => 'вьетнамский',
        'WA' => 'валлонский',
        'YI' => 'идиш',
        'ZH' => 'китайский',
        'ZU' => 'зулу',
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
     * Получить русский перевод для языка с использованием расширенного массива переводов
     * 
     * @param string $code ISO 639-1 код языка
     * @return string Русский перевод
     */
    public static function getRussianLanguageTranslation(string $code): string
    {
        $upperCode = strtoupper($code);

        // Сначала проверяем расширенный массив
        if (isset(self::$extendedRussianTranslations[$upperCode])) {
            return self::$extendedRussianTranslations[$upperCode];
        }

        // Затем fallback
        return self::$fallbackTranslations[$upperCode] ?? '';
    }

    /**
     * Проверить, есть ли перевод для данного кода (в любом из массивов)
     * 
     * @param string $code ISO код
     * @return bool
     */
    public static function hasTranslation(string $code): bool
    {
        $upperCode = strtoupper($code);
        return isset(self::$extendedRussianTranslations[$upperCode]) ||
            isset(self::$fallbackTranslations[$upperCode]);
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
     * Проверить, исключён ли код из обработки
     * 
     * @param string $code ISO код
     * @return bool
     */
    public static function isExcluded(string $code): bool
    {
        return in_array(strtoupper($code), self::$excludedCodes);
    }

    /**
     * Добавить код в список исключений
     * 
     * @param string|array $codes Код или массив кодов для исключения
     * @return void
     */
    public static function addToExcluded($codes): void
    {
        $codes = is_array($codes) ? $codes : [$codes];
        foreach ($codes as $code) {
            $upperCode = strtoupper($code);
            if (!in_array($upperCode, self::$excludedCodes)) {
                self::$excludedCodes[] = $upperCode;
            }
        }
    }

    /**
     * Убрать код из списка исключений
     * 
     * @param string|array $codes Код или массив кодов для удаления из исключений
     * @return void
     */
    public static function removeFromExcluded($codes): void
    {
        $codes = is_array($codes) ? $codes : [$codes];
        foreach ($codes as $code) {
            $upperCode = strtoupper($code);
            $key = array_search($upperCode, self::$excludedCodes);
            if ($key !== false) {
                unset(self::$excludedCodes[$key]);
                self::$excludedCodes = array_values(self::$excludedCodes); // Перенумеровать массив
            }
        }
    }

    /**
     * Получить список всех исключённых кодов
     * 
     * @return array
     */
    public static function getExcludedCodes(): array
    {
        return self::$excludedCodes;
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
        $entity = IsoEntity::countries()
            ->active()
            ->byIso2($iso2)
            ->first();

        if (!$entity) {
            return null;
        }

        return [
            'id' => $entity->id,
            'iso2' => $entity->iso_code_2,
            'iso3' => $entity->iso_code_3,
            'numeric_code' => $entity->numeric_code,
            'name' => $entity->getLocalizedName($languageCode),
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
        $entity = IsoEntity::languages()
            ->active()
            ->byIso2($iso2)
            ->first();

        if (!$entity) {
            return null;
        }

        return [
            'id' => $entity->id,
            'iso2' => $entity->iso_code_2,
            'iso3' => $entity->iso_code_3,
            'name' => $entity->getLocalizedName($languageCode),
            'original_name' => $entity->name,
        ];
    }

    /**
     * Получить все страны с переводами (с кешированием)
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getAllCountries(string $languageCode = 'en'): array
    {
        return IsoEntity::getCachedCountriesForFilters($languageCode);
    }

    /**
     * Получить все языки с переводами (с кешированием)
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getAllLanguages(string $languageCode = 'en'): array
    {
        return IsoEntity::getCachedLanguagesForFilters($languageCode);
    }

    /**
     * Получить популярные страны для фильтров (с кешированием)
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getPopularCountries(string $languageCode = 'en'): array
    {
        return IsoEntity::getCachedPopularCountries($languageCode);
    }

    /**
     * Получить карту стран ISO2 -> название (с кешированием)
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getCountryMap(string $languageCode = 'en'): array
    {
        return IsoEntity::getCachedCountryMap($languageCode);
    }

    /**
     * Получить карту языков ISO2 -> название (с кешированием)
     * 
     * @param string $languageCode Код языка для перевода
     * @return array
     */
    public static function getLanguageMap(string $languageCode = 'en'): array
    {
        return IsoEntity::getCachedLanguageMap($languageCode);
    }

    /**
     * Получить отфильтрованные языки с русскими переводами и исключениями
     * 
     * @param bool $useExtendedTranslations Использовать расширенные переводы
     * @param bool $excludeBlacklisted Исключить языки из чёрного списка
     * @return array
     */
    public static function getFilteredLanguagesWithRussianTranslations(
        bool $useExtendedTranslations = true,
        bool $excludeBlacklisted = true
    ): array {
        $languages = self::getAllLanguages('ru');
        $result = [];

        foreach ($languages as $language) {
            $isoCode = $language['iso_code_2'] ?? null;

            if (!$isoCode) {
                continue;
            }

            // Пропускаем исключённые языки
            if ($excludeBlacklisted && self::isExcluded($isoCode)) {
                continue;
            }

            $translatedName = $language['name'];

            // Если включены расширенные переводы и есть русский перевод
            if ($useExtendedTranslations && self::hasTranslation($isoCode)) {
                $russianTranslation = self::getRussianLanguageTranslation($isoCode);
                if (!empty($russianTranslation)) {
                    $translatedName = $russianTranslation;
                }
            }

            $result[] = [
                'id' => $language['id'],
                'iso2' => $isoCode,
                'iso3' => $language['iso_code_3'] ?? null,
                'name' => $translatedName,
                'original_name' => $language['original_name'] ?? $language['name'],
                'has_custom_translation' => self::hasTranslation($isoCode),
                'is_fallback' => self::isFallback($isoCode),
            ];
        }

        // Сортируем по переведённому имени
        usort($result, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Массовое обновление переводов в базе данных
     * 
     * @param array $updates Массив обновлений в формате ['iso_code' => 'translation']
     * @param string $languageCode Код языка для обновления (по умолчанию 'ru')
     * @return int Количество обновлённых записей
     */
    public static function bulkUpdateTranslations(array $updates, string $languageCode = 'ru'): int
    {
        $updatedCount = 0;

        foreach ($updates as $isoCode => $translation) {
            // Находим сущность по ISO коду
            $entity = IsoEntity::languages()
                ->active()
                ->byIso2($isoCode)
                ->first();

            if (!$entity) {
                continue;
            }

            // Обновляем или создаём перевод
            $translationRecord = IsoTranslation::updateOrCreate(
                [
                    'entity_id' => $entity->id,
                    'language_code' => $languageCode,
                ],
                [
                    'translated_name' => $translation,
                ]
            );

            if ($translationRecord->wasRecentlyCreated || $translationRecord->wasChanged()) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }
}
