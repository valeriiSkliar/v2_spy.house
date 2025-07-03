<?php

namespace App\Http\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * DTO для данных пагинации
 * Обеспечивает стандартизированную структуру пагинации для API
 */
class PaginationDTO implements Arrayable, Jsonable
{
    public function __construct(
        public int $total = 0,
        public int $perPage = 12,
        public int $currentPage = 1,
        public int $lastPage = 1,
        public int $from = 0,
        public int $to = 0,
        public bool $hasPages = false,
        public bool $hasMorePages = false,
        public ?string $nextPageUrl = null,
        public ?string $prevPageUrl = null,
    ) {
        // Автоматический расчет производных значений
        $this->recalculate();
    }

    /**
     * Создать DTO из фильтров и общего количества
     */
    public static function fromFiltersAndTotal(CreativesFiltersDTO $filtersDTO, int $total): self
    {
        return new self(
            total: $total,
            perPage: $filtersDTO->perPage,
            currentPage: $filtersDTO->page,
        );
    }

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total: $data['total'] ?? 0,
            perPage: $data['perPage'] ?? 12,
            currentPage: $data['currentPage'] ?? 1,
            lastPage: $data['lastPage'] ?? 1,
            from: $data['from'] ?? 0,
            to: $data['to'] ?? 0,
            hasPages: $data['hasPages'] ?? false,
            hasMorePages: $data['hasMorePages'] ?? false,
            nextPageUrl: $data['nextPageUrl'] ?? null,
            prevPageUrl: $data['prevPageUrl'] ?? null,
        );
    }

    /**
     * Создать пустую пагинацию
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Пересчитать производные значения
     */
    public function recalculate(): void
    {
        // Валидация и коррекция значений
        $this->total = max(0, $this->total);
        $this->perPage = max(1, $this->perPage);
        $this->currentPage = max(1, $this->currentPage);

        // Расчет lastPage
        $this->lastPage = $this->total > 0 ? (int)ceil($this->total / $this->perPage) : 1;

        // Коррекция currentPage если он больше lastPage
        if ($this->currentPage > $this->lastPage) {
            $this->currentPage = $this->lastPage;
        }

        // Расчет from и to
        if ($this->total === 0) {
            $this->from = 0;
            $this->to = 0;
        } else {
            $this->from = (($this->currentPage - 1) * $this->perPage) + 1;
            $this->to = min($this->currentPage * $this->perPage, $this->total);
        }

        // Расчет boolean значений
        $this->hasPages = $this->lastPage > 1;
        $this->hasMorePages = $this->currentPage < $this->lastPage;
    }

    /**
     * Установить URLs для навигации
     */
    public function withUrls(?string $baseUrl = null, array $params = []): self
    {
        if ($baseUrl) {
            // Предыдущая страница
            if ($this->currentPage > 1) {
                $prevParams = array_merge($params, ['page' => $this->currentPage - 1]);
                $this->prevPageUrl = $baseUrl . '?' . http_build_query($prevParams);
            }

            // Следующая страница
            if ($this->hasMorePages) {
                $nextParams = array_merge($params, ['page' => $this->currentPage + 1]);
                $this->nextPageUrl = $baseUrl . '?' . http_build_query($nextParams);
            }
        }

        return $this;
    }

    /**
     * Получить информацию о текущем состоянии
     */
    public function getInfo(): array
    {
        return [
            'showing' => $this->getShowingText(),
            'isFirstPage' => $this->isFirstPage(),
            'isLastPage' => $this->isLastPage(),
            'hasData' => $this->hasData(),
            'isEmpty' => $this->isEmpty(),
        ];
    }

    /**
     * Получить текст "Показано X из Y результатов"
     */
    public function getShowingText(): string
    {
        if ($this->total === 0) {
            return 'Результатов не найдено';
        }

        if ($this->total === 1) {
            return 'Показан 1 результат';
        }

        return "Показано {$this->from}-{$this->to} из {$this->total} результатов";
    }

    /**
     * Проверить является ли текущая страница первой
     */
    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    /**
     * Проверить является ли текущая страница последней
     */
    public function isLastPage(): bool
    {
        return $this->currentPage === $this->lastPage;
    }

    /**
     * Проверить есть ли данные
     */
    public function hasData(): bool
    {
        return $this->total > 0;
    }

    /**
     * Проверить пуста ли пагинация
     */
    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    /**
     * Получить номера страниц для отображения (для компонента пагинации)
     */
    public function getPageNumbers(int $showPages = 5): array
    {
        if ($this->lastPage <= $showPages) {
            return range(1, $this->lastPage);
        }

        $half = (int)floor($showPages / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->lastPage, $start + $showPages - 1);

        // Корректируем start если end достиг максимума
        if ($end - $start + 1 < $showPages) {
            $start = max(1, $end - $showPages + 1);
        }

        return range($start, $end);
    }

    /**
     * Получить данные для компонента пагинации Vue/React
     */
    public function getComponentProps(): array
    {
        return [
            'total' => $this->total,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'lastPage' => $this->lastPage,
            'hasPages' => $this->hasPages,
            'hasMorePages' => $this->hasMorePages,
            'hasPrevPage' => !$this->isFirstPage(),
            'hasNextPage' => $this->hasMorePages,
            'pageNumbers' => $this->getPageNumbers(),
            'showingText' => $this->getShowingText(),
            'info' => $this->getInfo(),
        ];
    }

    /**
     * Получить компактную версию для API
     */
    public function toCompactArray(): array
    {
        return [
            'total' => $this->total,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'lastPage' => $this->lastPage,
            'hasMorePages' => $this->hasMorePages,
        ];
    }

    /**
     * Валидация данных
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Валидация числовых полей
        $numericFields = ['total', 'perPage', 'currentPage', 'lastPage', 'from', 'to'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && (!is_numeric($data[$field]) || $data[$field] < 0)) {
                $errors[] = "{$field} must be non-negative integer";
            }
        }

        // Специальная валидация perPage
        if (isset($data['perPage']) && $data['perPage'] < 1) {
            $errors[] = "perPage must be at least 1";
        }

        // Специальная валидация currentPage
        if (isset($data['currentPage']) && $data['currentPage'] < 1) {
            $errors[] = "currentPage must be at least 1";
        }

        // Валидация boolean полей
        $booleanFields = ['hasPages', 'hasMorePages'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field]) && !is_bool($data[$field])) {
                $errors[] = "{$field} must be boolean";
            }
        }

        // Валидация URL полей
        $urlFields = ['nextPageUrl', 'prevPageUrl'];
        foreach ($urlFields as $field) {
            if (isset($data[$field]) && !is_null($data[$field]) && !is_string($data[$field])) {
                $errors[] = "{$field} must be string or null";
            }
        }

        return $errors;
    }

    /**
     * Создать DTO с валидацией
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
     * Имплементация Arrayable
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'lastPage' => $this->lastPage,
            'from' => $this->from,
            'to' => $this->to,
            'hasPages' => $this->hasPages,
            'hasMorePages' => $this->hasMorePages,
            'nextPageUrl' => $this->nextPageUrl,
            'prevPageUrl' => $this->prevPageUrl,
        ];
    }

    /**
     * Имплементация Jsonable
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Создать копию с измененными параметрами
     */
    public function clone(array $changes = []): self
    {
        $data = array_merge($this->toArray(), $changes);
        return self::fromArray($data);
    }

    /**
     * Перейти на определенную страницу
     */
    public function goToPage(int $page): self
    {
        return $this->clone(['currentPage' => $page]);
    }

    /**
     * Изменить количество элементов на странице
     */
    public function changePerPage(int $perPage): self
    {
        return $this->clone(['perPage' => $perPage, 'currentPage' => 1]);
    }
}