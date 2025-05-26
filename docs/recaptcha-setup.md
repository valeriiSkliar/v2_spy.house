# Настройка reCAPTCHA

## Получение ключей

1. Перейдите на [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Создайте новый сайт
3. Выберите тип reCAPTCHA v2 "I'm not a robot" Checkbox
4. Добавьте домены вашего сайта
5. Получите Site Key и Secret Key

## Настройка переменных окружения

Добавьте в ваш `.env` файл:

```env
# reCAPTCHA Configuration
NOCAPTCHA_SECRET=your_recaptcha_secret_key_here
NOCAPTCHA_SITEKEY=your_recaptcha_site_key_here
```

## Где используется reCAPTCHA

reCAPTCHA добавлена в следующие формы:

- Вход в систему (`/login`)
- Регистрация (`/register`)
- Сброс пароля (`/forgot-password`)

## Компоненты

- `resources/views/components/recaptcha.blade.php` - Blade компонент для отображения reCAPTCHA
- `resources/views/components/recaptcha-invisible.blade.php` - Компонент для невидимой reCAPTCHA
- `app/Rules/Recaptcha.php` - Правило валидации reCAPTCHA
- `app/Http/Middleware/VerifyRecaptcha.php` - Middleware для проверки reCAPTCHA
- `config/captcha.php` - Конфигурационный файл

## Использование middleware

Для применения middleware к группе маршрутов, добавьте в `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'recaptcha' => \App\Http\Middleware\VerifyRecaptcha::class,
];
```

Затем используйте в маршрутах:

```php
Route::middleware(['recaptcha'])->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
});
```

## Тестирование

### Команда тестирования

Для проверки конфигурации reCAPTCHA используйте команду:

```bash
php artisan recaptcha:test
```

Для тестирования конкретного токена:

```bash
php artisan recaptcha:test YOUR_RECAPTCHA_TOKEN
```

### Тестовые ключи Google

Для тестирования можно использовать тестовые ключи Google:

- Site key: `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`
- Secret key: `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`

**Внимание:** Тестовые ключи всегда возвращают успешный результат и должны использоваться только для разработки.
