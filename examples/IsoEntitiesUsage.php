<?php

/**
 * Примеры использования ISO моделей
 * 
 * Данный файл демонстрирует различные способы работы с ISO сущностями
 * (страны и языки) через созданные модели.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Frontend\IsoEntity;
use App\Helpers\IsoCodesHelper;

// Поиск страны через модель
echo "=== Поиск стран через модель ===\n";

$usa = IsoEntity::findCountryByIso2('US');
if ($usa) {
    echo "США найдены:\n";
    echo "- Название (EN): {$usa->getLocalizedName('en')}\n";
    echo "- Название (RU): {$usa->getLocalizedName('ru')}\n";
    echo "- ISO3: {$usa->iso_code_3}\n";
    echo "- Numeric: {$usa->numeric_code}\n";

    echo "- Доступные переводы:\n";
    foreach ($usa->getAvailableTranslations() as $lang => $translation) {
        echo "  - {$lang}: {$translation}\n";
    }
}

echo "\n=== Поиск языков через модель ===\n";

$english = IsoEntity::findLanguageByIso2('EN');
if ($english) {
    echo "Английский язык найден:\n";
    echo "- Название (EN): {$english->getLocalizedName('en')}\n";
    echo "- Название (RU): {$english->getLocalizedName('ru')}\n";
    echo "- ISO3: {$english->iso_code_3}\n";
}

echo "\n=== Использование Helper методов ===\n";

// Через Helper для обратной совместимости
$russia = IsoCodesHelper::getCountryFromDatabase('RU', 'ru');
if ($russia) {
    echo "Россия через Helper:\n";
    echo "- Название: {$russia['name']}\n";
    echo "- ISO3: {$russia['iso3']}\n";
    echo "- Оригинальное название: {$russia['original_name']}\n";
}

echo "\n=== Получение списков ===\n";

// Получение всех стран на русском языке
$russianCountries = IsoCodesHelper::getAllCountries('ru');
echo "Всего стран с русскими переводами: " . count($russianCountries) . "\n";

// Первые 5 стран в алфавитном порядке
echo "Первые 5 стран на русском:\n";
foreach (array_slice($russianCountries, 0, 5) as $country) {
    echo "- {$country['iso2']}: {$country['name']}\n";
}

// Получение всех языков на английском
$languages = IsoCodesHelper::getAllLanguages('en');
echo "\nВсего языков: " . count($languages) . "\n";

echo "\n=== Использование Scope методов ===\n";

// Количество активных стран
$activeCountries = IsoEntity::countries()->active()->count();
echo "Активные страны: {$activeCountries}\n";

// Количество активных языков
$activeLanguages = IsoEntity::languages()->active()->count();
echo "Активные языки: {$activeLanguages}\n";

// Поиск стран с определенным ISO3 кодом
$entityByIso3 = IsoEntity::countries()->byIso3('USA')->first();
if ($entityByIso3) {
    echo "Страна с ISO3 'USA': {$entityByIso3->getLocalizedName('ru')}\n";
}

echo "\n=== Проверка типов сущностей ===\n";

$someEntity = IsoEntity::first();
if ($someEntity) {
    echo "Первая сущность в БД:\n";
    echo "- Является страной: " . ($someEntity->isCountry() ? 'Да' : 'Нет') . "\n";
    echo "- Является языком: " . ($someEntity->isLanguage() ? 'Да' : 'Нет') . "\n";
    echo "- Тип: {$someEntity->type}\n";
    echo "- Название: {$someEntity->name}\n";
}

echo "\nПримеры использования завершены!\n";
