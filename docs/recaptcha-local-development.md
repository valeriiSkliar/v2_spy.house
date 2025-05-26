# Настройка reCAPTCHA для локальной разработки

## Проблема

При локальной разработке reCAPTCHA может не работать из-за неправильно настроенных доменов.

## Решение 1: Добавить localhost в Google reCAPTCHA Console

1. Перейдите в [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Выберите ваш сайт
3. В разделе "Домены" добавьте:

   ```
   localhost
   127.0.0.1
   dev.spy.house
   ```

   ⚠️ **ВАЖНО**: НЕ добавляйте порты (например :8000) - Google их не поддерживает!

## Решение 2: Использовать тестовые ключи Google

Для локальной разработки можете использовать тестовые ключи Google в файле `.env`:

```env
# Тестовые ключи Google reCAPTCHA (всегда проходят валидацию)
NOCAPTCHA_SITEKEY=[key]
NOCAPTCHA_SECRET=[key]
```

⚠️ **ВАЖНО**: Не используйте тестовые ключи в продакшене!

## Решение 3: Отключить reCAPTCHA для разработки

Создайте переменную окружения для отключения reCAPTCHA:

```env
RECAPTCHA_ENABLED=false
```

И модифицируйте правило валидации для учета этой настройки.

## Проверка настроек

После изменения настроек выполните:

```bash
php artisan config:clear
php artisan view:clear
php artisan recaptcha:test
```
