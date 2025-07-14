<?php

/**
 * Простой тест для проверки работы системы избранного
 * Запуск: php test-favorites-count.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Creative;
use App\Models\Favorite;

// Инициализируем Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Тест системы избранного ===\n\n";

try {
    // Найдем первого пользователя
    $user = User::first();
    if (!$user) {
        echo "❌ Пользователи не найдены в БД\n";
        exit(1);
    }

    echo "✅ Пользователь найден: {$user->email} (ID: {$user->id})\n";

    // Проверим метод getFavoritesCount()
    $favoritesCount = $user->getFavoritesCount();
    echo "📊 Количество избранных креативов: {$favoritesCount}\n";

    // Найдем креативы
    $creativesCount = Creative::count();
    echo "📈 Всего креативов в БД: {$creativesCount}\n";

    if ($creativesCount > 0) {
        $creative = Creative::first();
        echo "🎨 Первый креатив: {$creative->title} (ID: {$creative->id})\n";

        // Проверим, есть ли креатив в избранном
        $isFavorite = $user->hasFavoriteCreative($creative->id);
        echo "❤️ Креатив в избранном: " . ($isFavorite ? 'Да' : 'Нет') . "\n";
    }

    echo "\n=== Тест завершен успешно ===\n";
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
