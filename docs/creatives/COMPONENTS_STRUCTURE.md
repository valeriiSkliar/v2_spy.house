# Структура компонентов страницы креативов

Данный документ описывает новую модульную структуру компонентов после дробления монолитной страницы креативов.

## Цели дробления

1. **Максимальное переиспользование кода** - создание мелких переиспользуемых компонентов
2. **Асинхронная работа** - возможность независимой загрузки компонентов для SPA
3. **Получение разметки на бекенде** - возможность рендера отдельных компонентов серверной стороной
4. **Лучшая поддерживаемость** - простота внесения изменений в отдельные части

## Структура компонентов

### UI компоненты (components/ui/)

#### Базовые элементы формы

- `base-search-form.blade.php` - форма поиска с иконкой
- `base-select.blade.php` - базовый селект с опциями
- `date-picker.blade.php` - селектор дат с предустановленными диапазонами
- `multi-select.blade.php` - мульти-селект с поиском
- `copy-button.blade.php` - кнопка копирования с анимацией

#### Навигация и интерфейс

- `filter-tabs.blade.php` - табы фильтрации типов креативов (Push, InPage, Facebook, TikTok)
- `pagination.blade.php` - пагинация страниц
- `tracking-link.blade.php` - ссылка отслеживания с кнопкой открытия
- `hidden-text.blade.php` - скрываемый текст с функцией Show/Hide

### Компоненты креативов (components/creatives/)

#### Элементы креативов

- `creative-video.blade.php` - универсальный видео компонент
- `creative-item-push.blade.php` - элемент push креатива
- `creative-item-inpage.blade.php` - элемент inpage креатива
- `creative-item-social.blade.php` - элемент социального креатива

#### Детали креативов

- `creative-details-head.blade.php` - заголовок деталей с кнопками
- `creative-details-table.blade.php` - таблица деталей креатива
- `creative-social-metrics.blade.php` - социальные метрики (лайки, комментарии, репосты)
- `text-with-copy.blade.php` - текст с кнопкой копирования
- `similar-creatives.blade.php` - блок похожих креативов

#### Специализированные детали

- `push-details.blade.php` - полные детали push креатива
- `inpage-details.blade.php` - полные детали inpage креатива
- `social-details.blade.php` - полные детали социального креатива

### Основные файлы

#### Типы креативов (components/creatives/)

- `push.blade.php` - список push креативов
- `inpage.blade.php` - список inpage креативов
- `social.blade.php` - список социальных креативов
- `filter.blade.php` - фильтры (обновлен для использования UI компонентов)

#### Главная страница

- `pages/creatives/index.blade.php` - основная страница креативов

## Примеры использования

### Подключение UI компонентов

```blade
@include('components.ui.base-search-form')

@include('components.ui.base-select', [
    'placeholder' => 'Country',
    'options' => ['Option 1', 'Option 2']
])

@include('components.ui.date-picker', [
    'name' => 'dateCreation',
    'placeholder' => 'Date of creation'
])
```

### Подключение креативов

```blade
@include('components.creatives.creative-item-push', [
    'isActive' => true,
    'activeText' => 'Active: 3 day',
    'icon' => '/img/th-2.jpg',
    'isFavorite' => true
])

@include('components.creatives.creative-item-social', [
    'type' => 'facebook',
    'hasVideo' => true,
    'videoSrc' => '/img/video.mp4'
])
```

### Подключение деталей

```blade
@include('components.creatives.push-details')
@include('components.creatives.social-details', ['type' => 'facebook'])
```

## Параметры компонентов

### creative-item-push.blade.php

- `isActive` - активность креатива (boolean)
- `activeText` - текст статуса активности
- `icon` - путь к иконке
- `image` - путь к изображению
- `isFavorite` - в избранном ли (boolean)
- `title` - заголовок
- `description` - описание
- `network` - рекламная сеть
- `country` - страна
- `flagIcon` - путь к иконке флага
- `deviceType` - тип устройства
- `deviceText` - текст устройства

### creative-video.blade.php

- `class` - дополнительные CSS классы
- `image` - основное изображение
- `blurImage` - размытое изображение для фона
- `hasVideo` - есть ли видео (boolean)
- `duration` - длительность видео
- `videoSrc` - путь к видео файлу
- `controls` - показывать ли контролы (boolean)
- `showNewTab` - показывать ли кнопку открытия в новой вкладке

### base-select.blade.php

- `placeholder` - текст плейсхолдера
- `options` - массив опций

## Преимущества новой структуры

1. **Переиспользование** - UI компоненты можно использовать в других частях приложения
2. **Асинхронность** - каждый компонент может загружаться независимо через AJAX
3. **Тестируемость** - компоненты можно тестировать изолированно
4. **Поддерживаемость** - изменения в одном компоненте не затрагивают другие
5. **Консистентность** - единообразное поведение одинаковых элементов

## Совместимость

Вся существующая разметка и CSS остались без изменений. Компоненты используют те же классы и структуру HTML, что обеспечивает полную совместимость с текущими стилями и JavaScript кодом.
