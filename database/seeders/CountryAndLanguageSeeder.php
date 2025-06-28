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
        $this->command->info('🚀 Запуск заполнения стран и языков...');

        // Очистка таблиц
        $this->clearTables();

        // Получение данных для разных локалей
        $localesData = [
            'en' => IsoCodesHelper::getCountryAndLanguageDataByLocale('en_US.UTF-8'),
            'ru' => IsoCodesHelper::getCountryAndLanguageDataByLocale('ru_RU.UTF-8'),
        ];

        $this->command->info('📊 Информация об исключениях:');
        $excludedCodes = IsoCodesHelper::getExcludedCodes();
        if (!empty($excludedCodes)) {
            $this->command->info('Исключённые коды: ' . implode(', ', $excludedCodes));
        } else {
            $this->command->info('Нет исключённых кодов');
        }

        // Заполнение стран
        $this->command->info('🌍 Заполнение стран...');
        $this->seedCountries($localesData);

        // Заполнение языков
        $this->command->info('🗣️ Заполнение языков...');
        $this->seedLanguages($localesData);

        // Тестирование fallback переводов
        $this->command->info('🔍 Тестирование fallback переводов...');
        $this->testFallbackTranslations();

        // Проверка исключений
        $this->command->info('🚫 Проверка работы исключений...');
        $this->testExclusions();

        $this->command->info('✅ Заполнение завершено!');
    }

    private function clearTables(): void
    {
        $this->command->info('🗑️ Очистка таблиц...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        IsoTranslation::query()->delete();
        IsoEntity::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedCountries(array $localesData): void
    {
        $countries = $localesData['en']['countries'];
        $stats = [
            'total' => 0,
            'created' => 0,
            'excluded' => 0,
        ];

        foreach ($countries as $countryData) {
            $stats['total']++;
            $iso2 = $countryData['iso2'];

            // Проверяем исключения (если они применимы к странам)
            if (IsoCodesHelper::isExcluded($iso2)) {
                $stats['excluded']++;
                $this->command->info("Исключена страна: {$iso2} ({$countryData['name']})");
                continue;
            }

            // Создание ISO сущности для страны
            $entity = IsoEntity::create([
                'type' => 'country',
                'iso_code_2' => $iso2,
                'iso_code_3' => $countryData['iso3'],
                'numeric_code' => $countryData['numeric_code'],
                'name' => $countryData['name'],
                'is_active' => true,
            ]);

            $stats['created']++;

            // Создание переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findCountryTranslation(
                    $iso2,
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

        // Выводим статистику для стран
        $this->command->info("=== Статистика заполнения стран ===");
        $this->command->info("Всего стран: {$stats['total']}");
        $this->command->info("Создано сущностей: {$stats['created']}");
        $this->command->info("Исключено: {$stats['excluded']}");
    }

    private function seedLanguages(array $localesData): void
    {
        $languages = $localesData['en']['languages'];
        $stats = [
            'total' => 0,
            'created' => 0,
            'excluded' => 0,
            'fallback_used' => 0,
            'no_translation' => 0,
        ];

        foreach ($languages as $languageData) {
            $stats['total']++;
            $iso2 = $languageData['iso2'];

            // Проверяем исключения
            if (IsoCodesHelper::isExcluded($iso2)) {
                $stats['excluded']++;
                $this->command->info("Исключён язык: {$iso2} ({$languageData['name']})");
                continue;
            }

            // Создание ISO сущности для языка
            $entity = IsoEntity::create([
                'type' => 'language',
                'iso_code_2' => $iso2,
                'iso_code_3' => $languageData['iso3'],
                'numeric_code' => null, // У языков нет numeric кодов
                'name' => $languageData['name'],
                'is_active' => true,
            ]);

            $stats['created']++;

            // Создание переводов для каждой локали
            foreach ($localesData as $langCode => $localeData) {
                $translatedName = $this->findLanguageTranslation(
                    $iso2,
                    $localeData['languages'],
                    $langCode
                );

                if ($translatedName) {
                    $entity->translations()->create([
                        'language_code' => $langCode,
                        'translated_name' => $translatedName,
                    ]);

                    // Отслеживаем использование fallback переводов
                    if ($langCode === 'ru' && IsoCodesHelper::hasTranslation($iso2)) {
                        $isFromLibrary = $this->findLanguageInArray($iso2, $localeData['languages']);
                        if (!$isFromLibrary) {
                            $stats['fallback_used']++;
                            $this->command->info("Использован fallback перевод для {$iso2}: {$translatedName}");
                        }
                    }
                } else {
                    $stats['no_translation']++;
                    $this->command->warn("Нет перевода для {$iso2} на языке {$langCode}");
                }
            }
        }

        // Выводим статистику
        $this->command->info("=== Статистика заполнения языков ===");
        $this->command->info("Всего языков: {$stats['total']}");
        $this->command->info("Создано сущностей: {$stats['created']}");
        $this->command->info("Исключено: {$stats['excluded']}");
        $this->command->info("Использовано fallback переводов: {$stats['fallback_used']}");
        $this->command->info("Нет переводов: {$stats['no_translation']}");
    }

    /**
     * Проверить, есть ли язык в массиве (вспомогательный метод)
     */
    private function findLanguageInArray(string $iso2, array $languages): bool
    {
        foreach ($languages as $language) {
            if ($language['iso2'] === $iso2) {
                return true;
            }
        }
        return false;
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
        // Проверяем, не исключён ли данный код
        if (IsoCodesHelper::isExcluded($iso2)) {
            return null; // Пропускаем исключённые языки
        }

        // Сначала ищем в обычных переводах
        foreach ($languages as $language) {
            if ($language['iso2'] === $iso2) {
                return $language['name'];
            }
        }

        // Если не найден и это русский язык, используем расширенные переводы
        if ($langCode === 'ru' && IsoCodesHelper::hasTranslation($iso2)) {
            return IsoCodesHelper::getRussianLanguageTranslation($iso2);
        }

        return null;
    }

    private function testFallbackTranslations(): void
    {
        $testResults = [
            'tested' => 0,
            'passed' => 0,
            'failed' => 0,
            'excluded' => 0,
        ];

        // Получаем все языковые сущности из БД
        $languages = IsoEntity::languages()->get();

        foreach ($languages as $language) {
            $iso2 = $language->iso_code_2;
            $testResults['tested']++;

            // Проверяем исключения
            if (IsoCodesHelper::isExcluded($iso2)) {
                $testResults['excluded']++;
                continue;
            }

            // Проверяем, есть ли русский перевод в БД
            $dbTranslation = $language->translations()
                ->where('language_code', 'ru')
                ->first();

            // Проверяем, есть ли fallback перевод
            $fallbackTranslation = IsoCodesHelper::getRussianLanguageTranslation($iso2);

            if ($dbTranslation) {
                // Если перевод есть в БД
                if (!empty($fallbackTranslation) && $dbTranslation->translated_name !== $fallbackTranslation) {
                    $this->command->warn("⚠️ Расхождение для {$iso2}: БД='{$dbTranslation->translated_name}', Fallback='{$fallbackTranslation}'");
                }
                $testResults['passed']++;
            } else {
                // Если перевода нет в БД, но есть fallback
                if (!empty($fallbackTranslation)) {
                    $this->command->error("❌ Нет перевода в БД для {$iso2}, но есть fallback: '{$fallbackTranslation}'");
                    $testResults['failed']++;
                } else {
                    // Ни БД, ни fallback - это нормально для некоторых языков
                    $testResults['passed']++;
                }
            }
        }

        // Выводим результаты тестирования
        $this->command->info("=== Результаты тестирования fallback переводов ===");
        $this->command->info("Протестировано языков: {$testResults['tested']}");
        $this->command->info("Прошли проверку: {$testResults['passed']}");
        $this->command->info("Не прошли проверку: {$testResults['failed']}");
        $this->command->info("Исключено из проверки: {$testResults['excluded']}");

        // Проверяем специфические fallback переводы
        $this->testSpecificFallbacks();
    }

    private function testSpecificFallbacks(): void
    {
        $this->command->info("=== Тестирование специфических fallback переводов ===");

        $specificTests = [
            'DV' => 'мальдивский',
            'KL' => 'гренландский',
            'EL' => 'греческий',
            'NE' => 'непальский',
            'OS' => 'осетинский',
            'PA' => 'панджаби',
            'RM' => 'романшский',
            'SI' => 'сингальский',
        ];

        foreach ($specificTests as $iso2 => $expectedTranslation) {
            if (IsoCodesHelper::isExcluded($iso2)) {
                $this->command->info("🚫 {$iso2} исключён из тестирования");
                continue;
            }

            $actualTranslation = IsoCodesHelper::getRussianLanguageTranslation($iso2);

            if ($actualTranslation === $expectedTranslation) {
                $this->command->info("✅ {$iso2}: '{$actualTranslation}' - корректно");
            } else {
                $this->command->error("❌ {$iso2}: ожидался '{$expectedTranslation}', получен '{$actualTranslation}'");
            }
        }
    }

    private function testExclusions(): void
    {
        $this->command->info("=== Тестирование работы исключений ===");

        // Тестируем языковые коды (исключения применяются к языкам)
        $languageTestCases = [
            'CU' => 'церковнославянский', // Этот код исключён
            'SH' => 'сербскохорватский',  // Этот код также исключён
            'RU' => 'русский',            // Не исключён
            'EN' => 'английский',         // Не исключён
        ];

        foreach ($languageTestCases as $iso2 => $expectedTranslation) {
            $isExcluded = IsoCodesHelper::isExcluded($iso2);
            $hasTranslation = IsoCodesHelper::hasTranslation($iso2);
            $actualTranslation = IsoCodesHelper::getRussianLanguageTranslation($iso2);

            if ($isExcluded) {
                $this->command->info("🚫 {$iso2} корректно исключён из обработки");

                // Проверяем, что исключённый код не был добавлен в БД
                $entityExists = IsoEntity::languages()->byIso2($iso2)->exists();
                if (!$entityExists) {
                    $this->command->info("✅ {$iso2} корректно отсутствует в БД");
                } else {
                    $this->command->error("❌ {$iso2} присутствует в БД, хотя должен быть исключён");
                }
            } else {
                if ($hasTranslation && $actualTranslation === $expectedTranslation) {
                    $this->command->info("✅ {$iso2}: '{$actualTranslation}' - корректно обработан");
                } else {
                    $this->command->warn("⚠️ {$iso2}: ожидался '{$expectedTranslation}', получен '{$actualTranslation}'");
                }
            }
        }

        // Тестируем динамическое добавление/удаление исключений
        $this->testDynamicExclusions();
    }

    private function testDynamicExclusions(): void
    {
        $this->command->info("=== Тестирование динамических исключений ===");

        $testCode = 'XX'; // Несуществующий код для тестирования

        // Тест добавления в исключения
        IsoCodesHelper::addToExcluded($testCode);
        if (IsoCodesHelper::isExcluded($testCode)) {
            $this->command->info("✅ Код {$testCode} успешно добавлен в исключения");
        } else {
            $this->command->error("❌ Не удалось добавить {$testCode} в исключения");
        }

        // Тест удаления из исключений
        IsoCodesHelper::removeFromExcluded($testCode);
        if (!IsoCodesHelper::isExcluded($testCode)) {
            $this->command->info("✅ Код {$testCode} успешно удалён из исключений");
        } else {
            $this->command->error("❌ Не удалось удалить {$testCode} из исключений");
        }

        // Показываем текущий список исключений
        $excludedCodes = IsoCodesHelper::getExcludedCodes();
        $this->command->info("📋 Текущие исключения: " . implode(', ', $excludedCodes));
    }
}
