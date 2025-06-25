<?php

namespace Database\Seeders;

use App\Helpers\IsoCodesHelper;
use App\Models\Frontend\IsoEntity;
use App\Models\Frontend\IsoTranslation;
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
        IsoTranslation::query()->delete();
        IsoEntity::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedCountries(array $localesData): void
    {
        $countries = $localesData['en']['countries'];

        foreach ($countries as $countryData) {
            // Создание ISO сущности для страны
            $entity = IsoEntity::create([
                'type' => 'country',
                'iso_code_2' => $countryData['iso2'],
                'iso_code_3' => $countryData['iso3'],
                'numeric_code' => $countryData['numeric_code'],
                'name' => $countryData['name'],
                'is_active' => true,
            ]);

            // Создание переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findCountryTranslation(
                    $countryData['iso2'],
                    $localeData['countries']
                );

                if ($translatedName) {
                    $entity->translations()->create([
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
            // Создание ISO сущности для языка
            $entity = IsoEntity::create([
                'type' => 'language',
                'iso_code_2' => $languageData['iso2'],
                'iso_code_3' => $languageData['iso3'],
                'numeric_code' => null, // У языков нет numeric кодов
                'name' => $languageData['name'],
                'is_active' => true,
            ]);

            // Создание переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findLanguageTranslation(
                    $languageData['iso2'],
                    $localeData['languages'],
                    $langCode
                );

                if ($translatedName) {
                    $entity->translations()->create([
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
