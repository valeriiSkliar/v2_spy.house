<?php

namespace Database\Seeders;

use App\Helpers\IsoCodesHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryAndLanguageSeeder extends Seeder
{
    public function run(): void
    {
        // Очистка таблиц
        $this->clearTables();

        // Получение данных для разных локалей
        $localesData = [
            'en' => IsoCodesHelper::getCountryAndLanguageDataByLocale('en_US.UTF-8'),
            'ru' => IsoCodesHelper::getCountryAndLanguageDataByLocale('ru_RU.UTF-8'),
        ];

        // Заполнение стран
        $this->seedCountries($localesData);

        // Заполнение языков
        $this->seedLanguages($localesData);
    }

    private function clearTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('iso_translations')->delete();
        DB::table('iso_entities')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedCountries(array $localesData): void
    {
        $countries = $localesData['en']['countries'];

        foreach ($countries as $countryData) {
            // Вставка основных данных страны
            $entityId = DB::table('iso_entities')->insertGetId([
                'type' => 'country',
                'iso_code_2' => $countryData['iso2'],
                'iso_code_3' => $countryData['iso3'],
                'numeric_code' => $countryData['numeric_code'],
                'name' => $countryData['name'],
                'is_active' => true,
            ]);

            // Вставка переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findCountryTranslation(
                    $countryData['iso2'],
                    $localeData['countries']
                );

                if ($translatedName) {
                    DB::table('iso_translations')->insert([
                        'entity_id' => $entityId,
                        'language_code' => $langCode,
                        'translated_name' => $translatedName,
                    ]);
                }
            }
        }
    }

    private function seedLanguages(array $localesData): void
    {
        $languages = $localesData['en']['languages'];

        foreach ($languages as $languageData) {
            // Вставка основных данных языка
            $entityId = DB::table('iso_entities')->insertGetId([
                'type' => 'language',
                'iso_code_2' => $languageData['iso2'],
                'iso_code_3' => $languageData['iso3'],
                'numeric_code' => null, // У языков нет numeric кодов
                'name' => $languageData['name'],
                'is_active' => true,
            ]);

            // Вставка переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findLanguageTranslation(
                    $languageData['iso2'],
                    $localeData['languages'],
                    $langCode
                );

                if ($translatedName) {
                    DB::table('iso_translations')->insert([
                        'entity_id' => $entityId,
                        'language_code' => $langCode,
                        'translated_name' => $translatedName,
                    ]);
                }
            }
        }
    }

    private function findCountryTranslation(string $iso2, array $countries): ?string
    {
        foreach ($countries as $country) {
            if ($country['iso2'] === $iso2) {
                return $country['name'];
            }
        }
        return null;
    }

    private function findLanguageTranslation(string $iso2, array $languages, string $langCode): ?string
    {
        // Сначала ищем в обычных переводах
        foreach ($languages as $language) {
            if ($language['iso2'] === $iso2) {
                return $language['name'];
            }
        }

        // Если не найден и это русский язык, используем fallback
        if ($langCode === 'ru' && IsoCodesHelper::isFallback($iso2)) {
            return IsoCodesHelper::getRussianLanguageTranslation($iso2);
        }

        return null;
    }
}
