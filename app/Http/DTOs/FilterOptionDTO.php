<?php

namespace App\Http\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * DTO для опций селектов/фильтров
 * Обеспечивает стандартизированную структуру для dropdown компонентов
 */
class FilterOptionDTO implements Arrayable, Jsonable
{
    public function __construct(
        // Основные поля опции
        public string $value,
        public string $label,

        // Дополнительные атрибуты
        public bool $disabled = false,
        public bool $selected = false,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $logo = null,
        public ?string $group = null,
        public ?int $count = null,

        // Метаданные
        public array $metadata = [],
        public ?string $color = null,
        public ?int $sortOrder = null,

        // Для вложенных опций
        public array $children = [],
        public ?string $parentValue = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'] ?? '',
            label: $data['label'] ?? '',
            disabled: $data['disabled'] ?? false,
            selected: $data['selected'] ?? false,
            description: $data['description'] ?? null,
            icon: $data['icon'] ?? null,
            logo: $data['logo'] ?? null,
            group: $data['group'] ?? null,
            count: isset($data['count']) ? (int)$data['count'] : null,
            metadata: $data['metadata'] ?? [],
            color: $data['color'] ?? null,
            sortOrder: isset($data['sortOrder']) ? (int)$data['sortOrder'] : null,
            children: self::processChildren($data['children'] ?? []),
            parentValue: $data['parentValue'] ?? null,
        );
    }

    /**
     * Создать коллекцию опций из массива
     */
    public static function collection(array $items): array
    {
        return array_map(
            fn(array $item) => self::fromArray($item),
            $items
        );
    }

    /**
     * Создать коллекцию простых опций (value/label)
     */
    public static function simpleCollection(array $items): array
    {
        return array_map(
            fn($item) => is_array($item)
                ? self::fromArray($item)
                : self::simple((string)$item, (string)$item),
            $items
        );
    }

    /**
     * Создать простую опцию
     */
    public static function simple(string $value, string $label, bool $selected = false): self
    {
        return new self(
            value: $value,
            label: $label,
            selected: $selected
        );
    }

    /**
     * Создать опцию с количеством
     */
    public static function withCount(string $value, string $label, int $count, bool $selected = false): self
    {
        return new self(
            value: $value,
            label: $label,
            count: $count,
            selected: $selected
        );
    }

    /**
     * Создать опцию с иконкой
     */
    public static function withIcon(string $value, string $label, string $icon, bool $selected = false): self
    {
        return new self(
            value: $value,
            label: $label,
            icon: $icon,
            selected: $selected
        );
    }

    /**
     * Создать группированную опцию
     */
    public static function grouped(string $value, string $label, string $group, bool $selected = false): self
    {
        return new self(
            value: $value,
            label: $label,
            group: $group,
            selected: $selected
        );
    }

    /**
     * Создать опции для стран из helper (поддерживает мультиселект)
     */
    public static function countries(array $countries, array $selectedCountries = []): array
    {
        $options = [];

        foreach ($countries as $key => $country) {
            if (is_array($country)) {
                // Новый формат: массив с value/label/code
                $code = $country['value'] ?? $country['code'] ?? $country['iso_code_2'] ?? $key;
                $label = $country['label'] ?? $country['name'] ?? $code;
            } else {
                // Старый формат: ключ => значение (ассоциативный массив)
                // или числовой массив со строками
                if (is_string($key) && !is_numeric($key)) {
                    // Ассоциативный массив: 'US' => 'United States'
                    $code = $key;
                    $label = is_string($country) ? $country : $key;
                } else {
                    // Числовой массив: ['US', 'GB', 'DE']
                    $code = is_string($country) ? $country : '';
                    $label = is_string($country) ? $country : '';
                }
            }

            if (empty($code)) {
                continue; // Пропускаем пустые коды
            }

            $options[] = self::simple($code, (string)$label, in_array($code, $selectedCountries));
        }

        return $options;
    }

    /**
     * Создать опции для языков
     */
    public static function languages(array $languages, array $selectedLanguages = []): array
    {
        $options = [];

        foreach ($languages as $key => $language) {
            if (is_array($language)) {
                // Новый формат: массив с value/label/code
                $code = $language['value'] ?? $language['code'] ?? $language['iso_code_2'] ?? $key;
                $label = $language['label'] ?? $language['name'] ?? $code;
            } else {
                // Старый формат: ключ => значение (ассоциативный массив)
                // или числовой массив со строками
                if (is_string($key) && !is_numeric($key)) {
                    // Ассоциативный массив: 'en' => 'English'
                    $code = $key;
                    $label = is_string($language) ? $language : $key;
                } else {
                    // Числовой массив: ['en', 'ru', 'de']
                    $code = is_string($language) ? $language : '';
                    $label = is_string($language) ? $language : '';
                }
            }

            if (empty($code)) {
                continue; // Пропускаем пустые коды
            }

            $options[] = self::simple($code, (string)$label, in_array($code, $selectedLanguages));
        }

        return $options;
    }

    /**
     * Создать опции для сортировки
     */
    public static function sortOptions(array $selectedSortBy = []): array
    {
        $sortOptions = [
            'byCreationDate' => 'По дате создания',
            'byActivity' => 'По дням активности',
            'byPopularity' => 'По популярности',
        ];

        $options = [];
        foreach ($sortOptions as $value => $label) {
            $options[] = self::simple($value, $label, in_array($value, $selectedSortBy));
        }

        return $options;
    }

    /**
     * Создать опции для периодов отображения
     */
    public static function dateRangeOptions(?string $selectedRange = null): array
    {
        $ranges = [
            'default' => 'Все время',
            'today' => 'Сегодня',
            'yesterday' => 'Вчера',
            'last7' => 'За последние 7 дней',
            'last30' => 'За последние 30 дней',
            'last90' => 'За последние 90 дней',
            'thisMonth' => 'За текущий месяц',
            'lastMonth' => 'За прошлый месяц',
            'thisYear' => 'За текущий год',
            'lastYear' => 'За прошлый год',
        ];

        $options = [];
        foreach ($ranges as $value => $label) {
            $options[] = self::simple($value, $label, $selectedRange === $value);
        }

        return $options;
    }

    /**
     * Создать опции для количества на странице
     */
    public static function perPageOptions(int $selectedPerPage = 12): array
    {
        $perPageValues = [6, 12, 24, 48, 96];

        $options = [];
        foreach ($perPageValues as $value) {
            $options[] = self::simple((string)$value, (string)$value, $value === $selectedPerPage);
        }

        return $options;
    }

    /**
     * Создать опции для сетей с количеством
     */
    public static function advertisingNetworksWithCount($networks, array $selectedNetworks = [], array $counts = []): array
    {
        // Конвертируем Collection в массив если нужно
        if (is_object($networks) && method_exists($networks, 'toArray')) {
            $networks = $networks->toArray();
        }

        // Убеждаемся что это массив
        if (!is_array($networks)) {
            $networks = [];
        }

        $options = [];

        foreach ($networks as $network) {
            $value = is_array($network) ? ($network['value'] ?? $network['code'] ?? $network['name'] ?? '') : (string)$network;
            $label = is_array($network) ? ($network['label'] ?? $network['name'] ?? $value) : (string)$network;
            $logo = is_array($network) ? ($network['logo'] ?? null) : null;
            $count = $counts[$value] ?? null;

            if (empty($value)) {
                continue; // Пропускаем пустые значения
            }

            $option = new self(
                value: (string)$value,
                label: (string)$label,
                selected: in_array($value, $selectedNetworks),
                count: $count,
                logo: $logo  // Используем logo для логотипа
            );

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Создать опции для размеров изображений
     */
    public static function imageSizeOptions(array $selectedSizes = []): array
    {
        $sizes = [
            '1x1' => '1x1 (Square)',
            '16x9' => '16x9 (Landscape)',
            '9x16' => '9x16 (Portrait)',
            '3x2' => '3x2 (Classic)',
            '2x3' => '2x3 (Portrait)',
            '4x3' => '4x3 (Standard)',
            '3x4' => '3x4 (Portrait)',
            '21x9' => '21x9 (Ultra-wide)',
        ];

        $options = [];
        foreach ($sizes as $value => $label) {
            $options[] = self::simple($value, $label, in_array($value, $selectedSizes));
        }

        return $options;
    }

    /**
     * Обработать дочерние элементы
     */
    private static function processChildren(array $children): array
    {
        return array_map(
            fn(array $child) => self::fromArray($child),
            $children
        );
    }

    /**
     * Группировать опции по полю group
     */
    public static function groupOptions(array $options): array
    {
        $grouped = [];

        foreach ($options as $option) {
            if ($option instanceof self) {
                $group = $option->group ?? 'default';
                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][] = $option;
            }
        }

        return $grouped;
    }

    /**
     * Сортировать опции
     */
    public static function sortCollection(array $options, string $sortBy = 'label'): array
    {
        usort($options, function ($a, $b) use ($sortBy) {
            if (!($a instanceof self) || !($b instanceof self)) {
                return 0;
            }

            switch ($sortBy) {
                case 'value':
                    return strcmp($a->value, $b->value);
                case 'count':
                    return ($b->count ?? 0) <=> ($a->count ?? 0);
                case 'sortOrder':
                    return ($a->sortOrder ?? 999) <=> ($b->sortOrder ?? 999);
                default: // label
                    return strcmp($a->label, $b->label);
            }
        });

        return $options;
    }

    /**
     * Фильтровать опции
     */
    public static function filterOptions(array $options, callable $callback): array
    {
        return array_filter($options, $callback);
    }

    /**
     * Найти опцию по значению
     */
    public static function findByValue(array $options, string $value): ?self
    {
        foreach ($options as $option) {
            if ($option instanceof self && $option->value === $value) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Получить выбранные опции
     */
    public static function getSelected(array $options): array
    {
        return array_filter($options, fn($option) => $option instanceof self && $option->selected);
    }

    /**
     * Установить выбранные значения
     */
    public static function setSelected(array $options, array $selectedValues): array
    {
        return array_map(function ($option) use ($selectedValues) {
            if ($option instanceof self) {
                $option->selected = in_array($option->value, $selectedValues);
            }
            return $option;
        }, $options);
    }

    /**
     * Получить только значения
     */
    public static function getValues(array $options): array
    {
        return array_map(
            fn($option) => $option instanceof self ? $option->value : null,
            $options
        );
    }

    /**
     * Получить только метки
     */
    public static function getLabels(array $options): array
    {
        return array_map(
            fn($option) => $option instanceof self ? $option->label : null,
            $options
        );
    }

    /**
     * Валидация данных
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Обязательные поля
        if (empty($data['value'])) {
            $errors[] = 'value is required';
        }

        if (empty($data['label'])) {
            $errors[] = 'label is required';
        }

        // Валидация типов
        if (isset($data['disabled']) && !is_bool($data['disabled'])) {
            $errors[] = 'disabled must be boolean';
        }

        if (isset($data['selected']) && !is_bool($data['selected'])) {
            $errors[] = 'selected must be boolean';
        }

        if (isset($data['count']) && (!is_numeric($data['count']) || $data['count'] < 0)) {
            $errors[] = 'count must be non-negative integer';
        }

        if (isset($data['sortOrder']) && (!is_numeric($data['sortOrder']) || $data['sortOrder'] < 0)) {
            $errors[] = 'sortOrder must be non-negative integer';
        }

        if (isset($data['metadata']) && !is_array($data['metadata'])) {
            $errors[] = 'metadata must be array';
        }

        if (isset($data['children']) && !is_array($data['children'])) {
            $errors[] = 'children must be array';
        }

        return $errors;
    }

    /**
     * Создать с валидацией
     */
    public static function fromArrayWithValidation(array $data): self
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        return self::fromArray($data);
    }

    /**
     * Клонировать с изменениями
     */
    public function clone(array $changes = []): self
    {
        $data = array_merge($this->toArray(), $changes);
        return self::fromArray($data);
    }

    /**
     * Установить как выбранную
     */
    public function select(): self
    {
        $this->selected = true;
        return $this;
    }

    /**
     * Убрать выбор
     */
    public function deselect(): self
    {
        $this->selected = false;
        return $this;
    }

    /**
     * Отключить опцию
     */
    public function disable(): self
    {
        $this->disabled = true;
        return $this;
    }

    /**
     * Включить опцию
     */
    public function enable(): self
    {
        $this->disabled = false;
        return $this;
    }

    /**
     * Добавить метаданные
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Установить количество
     */
    public function setCount(int $count): self
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Проверить активность опции
     */
    public function isActive(): bool
    {
        return !$this->disabled;
    }

    /**
     * Проверить выбрана ли опция
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }

    /**
     * Проверить есть ли дочерние элементы
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Получить компактную версию для API
     */
    public function toCompactArray(): array
    {
        $result = [
            'value' => $this->value,
            'label' => $this->label,
        ];

        // Добавляем только значимые поля
        if ($this->selected) {
            $result['selected'] = true;
        }

        if ($this->disabled) {
            $result['disabled'] = true;
        }

        if ($this->count !== null) {
            $result['count'] = $this->count;
        }

        if ($this->icon) {
            $result['icon'] = $this->icon;
        }

        if ($this->logo) {
            $result['logo'] = $this->logo;
        }

        return $result;
    }

    /**
     * Получить версию для Vue/React селектов
     */
    public function toSelectFormat(): array
    {
        return [
            'value' => $this->value,
            'text' => $this->label,
            'disabled' => $this->disabled,
            'selected' => $this->selected,
        ];
    }

    /**
     * Имплементация Arrayable
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'disabled' => $this->disabled,
            'selected' => $this->selected,
            'description' => $this->description,
            'icon' => $this->icon,
            'logo' => $this->logo,
            'group' => $this->group,
            'count' => $this->count,
            'metadata' => $this->metadata,
            'color' => $this->color,
            'sortOrder' => $this->sortOrder,
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'parentValue' => $this->parentValue,
        ];
    }

    /**
     * Имплементация Jsonable
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
