<?php

namespace App\Http\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * DTO для API ответов со списком креативов
 * Обеспечивает стандартизированную структуру ответа для фронтенда
 */
class CreativesResponseDTO implements Arrayable, Jsonable
{
    public function __construct(
        // Основные данные
        public array $items = [],
        public PaginationDTO $pagination = new PaginationDTO(),
        
        // Метаданные
        public bool $hasSearch = false,
        public int $activeFiltersCount = 0,
        public bool $hasActiveFilters = false,
        public string $cacheKey = '',
        public array $appliedFilters = [],
        public array $activeFilters = [],
        
        // Информация о загрузке/состоянии
        public bool $isLoading = false,
        public ?string $error = null,
        public string $status = 'success',
        public ?string $timestamp = null,
        
        // Дополнительные опции
        public array $availableFilters = [],
        public array $filterOptions = [],
    ) {
        $this->timestamp = $this->timestamp ?? now()->toISOString();
    }

    /**
     * Создать DTO из данных контроллера
     */
    public static function fromControllerData(
        array $items,
        CreativesFiltersDTO $filtersDTO,
        int $totalCount
    ): self {
        return new self(
            items: $items,
            pagination: PaginationDTO::fromFiltersAndTotal($filtersDTO, $totalCount),
            hasSearch: !empty($filtersDTO->searchKeyword),
            activeFiltersCount: $filtersDTO->getActiveFiltersCount(),
            hasActiveFilters: $filtersDTO->hasActiveFilters(),
            cacheKey: $filtersDTO->getCacheKey(),
            appliedFilters: $filtersDTO->toArray(),
            activeFilters: $filtersDTO->getActiveFilters(),
        );
    }

    /**
     * Создать успешный ответ
     */
    public static function success(
        array $items,
        CreativesFiltersDTO $filtersDTO,
        int $totalCount
    ): self {
        return self::fromControllerData($items, $filtersDTO, $totalCount)
            ->withStatus('success');
    }

    /**
     * Создать ответ с ошибкой
     */
    public static function error(string $error, array $filters = []): self
    {
        return new self(
            items: [],
            pagination: new PaginationDTO(),
            status: 'error',
            error: $error,
            appliedFilters: $filters,
        );
    }

    /**
     * Создать ответ для состояния загрузки
     */
    public static function loading(array $filters = []): self
    {
        return new self(
            items: [],
            pagination: new PaginationDTO(),
            status: 'loading',
            isLoading: true,
            appliedFilters: $filters,
        );
    }

    /**
     * Создать пустой ответ (нет данных)
     */
    public static function empty(CreativesFiltersDTO $filtersDTO): self
    {
        return new self(
            items: [],
            pagination: PaginationDTO::fromFiltersAndTotal($filtersDTO, 0),
            hasSearch: !empty($filtersDTO->searchKeyword),
            activeFiltersCount: $filtersDTO->getActiveFiltersCount(),
            hasActiveFilters: $filtersDTO->hasActiveFilters(),
            appliedFilters: $filtersDTO->toArray(),
            activeFilters: $filtersDTO->getActiveFilters(),
            status: 'empty',
        );
    }

    /**
     * Установить опции фильтров
     */
    public function withFilterOptions(array $filterOptions): self
    {
        $this->filterOptions = $filterOptions;
        return $this;
    }

    /**
     * Установить доступные фильтры
     */
    public function withAvailableFilters(array $availableFilters): self
    {
        $this->availableFilters = $availableFilters;
        return $this;
    }

    /**
     * Установить статус
     */
    public function withStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Установить ошибку
     */
    public function withError(string $error): self
    {
        $this->error = $error;
        $this->status = 'error';
        return $this;
    }

    /**
     * Установить состояние загрузки
     */
    public function withLoading(bool $isLoading = true): self
    {
        $this->isLoading = $isLoading;
        if ($isLoading) {
            $this->status = 'loading';
        }
        return $this;
    }

    /**
     * Добавить дополнительные метаданные
     */
    public function withMeta(array $meta): self
    {
        foreach ($meta as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Проверить успешность ответа
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Проверить наличие ошибки
     */
    public function hasError(): bool
    {
        return $this->status === 'error' || !empty($this->error);
    }

    /**
     * Проверить пустоту результата
     */
    public function isEmpty(): bool
    {
        return empty($this->items) && $this->status !== 'error' && $this->status !== 'loading';
    }

    /**
     * Получить количество элементов
     */
    public function getItemsCount(): int
    {
        return count($this->items);
    }

    /**
     * Получить краткую статистику для логирования
     */
    public function getStats(): array
    {
        return [
            'status' => $this->status,
            'itemsCount' => $this->getItemsCount(),
            'totalCount' => $this->pagination->total,
            'currentPage' => $this->pagination->currentPage,
            'hasFilters' => $this->hasActiveFilters,
            'filtersCount' => $this->activeFiltersCount,
            'hasSearch' => $this->hasSearch,
            'cacheKey' => $this->cacheKey,
        ];
    }

    /**
     * Получить версию для API (стандартный Laravel формат)
     */
    public function toApiResponse(): array
    {
        $response = [
            'status' => $this->status,
            'data' => [
                'items' => $this->items,
                'pagination' => $this->pagination->toArray(),
                'meta' => [
                    'hasSearch' => $this->hasSearch,
                    'activeFiltersCount' => $this->activeFiltersCount,
                    'hasActiveFilters' => $this->hasActiveFilters,
                    'cacheKey' => $this->cacheKey,
                    'appliedFilters' => $this->appliedFilters,
                    'activeFilters' => $this->activeFilters,
                    'timestamp' => $this->timestamp,
                ]
            ]
        ];

        // Добавляем ошибку если есть
        if ($this->hasError()) {
            $response['error'] = $this->error;
        }

        // Добавляем состояние загрузки если есть
        if ($this->isLoading) {
            $response['data']['meta']['isLoading'] = true;
        }

        // Добавляем опции фильтров если есть
        if (!empty($this->filterOptions)) {
            $response['data']['filterOptions'] = $this->filterOptions;
        }

        // Добавляем доступные фильтры если есть
        if (!empty($this->availableFilters)) {
            $response['data']['availableFilters'] = $this->availableFilters;
        }

        return $response;
    }

    /**
     * Получить компактную версию для мобильных устройств
     */
    public function toCompactArray(): array
    {
        return [
            'status' => $this->status,
            'items' => $this->items,
            'pagination' => $this->pagination->toCompactArray(),
            'meta' => [
                'hasSearch' => $this->hasSearch,
                'hasFilters' => $this->hasActiveFilters,
                'timestamp' => $this->timestamp,
            ]
        ];
    }

    /**
     * Валидация данных перед созданием DTO
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Валидация items (должен быть массив)
        if (isset($data['items']) && !is_array($data['items'])) {
            $errors[] = 'items must be an array';
        }

        // Валидация pagination
        if (isset($data['pagination']) && !($data['pagination'] instanceof PaginationDTO) && !is_array($data['pagination'])) {
            $errors[] = 'pagination must be PaginationDTO instance or array';
        }

        // Валидация boolean полей
        $booleanFields = ['hasSearch', 'hasActiveFilters', 'isLoading'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field]) && !is_bool($data[$field])) {
                $errors[] = "{$field} must be boolean";
            }
        }

        // Валидация числовых полей
        if (isset($data['activeFiltersCount']) && (!is_numeric($data['activeFiltersCount']) || $data['activeFiltersCount'] < 0)) {
            $errors[] = 'activeFiltersCount must be non-negative integer';
        }

        // Валидация status
        if (isset($data['status'])) {
            $validStatuses = ['success', 'error', 'loading', 'empty'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors[] = 'status must be one of: ' . implode(', ', $validStatuses);
            }
        }

        // Валидация массивов
        $arrayFields = ['appliedFilters', 'activeFilters', 'availableFilters', 'filterOptions'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                $errors[] = "{$field} must be an array";
            }
        }

        return $errors;
    }

    /**
     * Создать DTO из массива с валидацией
     */
    public static function fromArray(array $data): self
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        // Обработка pagination
        $pagination = $data['pagination'] ?? new PaginationDTO();
        if (is_array($pagination)) {
            $pagination = PaginationDTO::fromArray($pagination);
        }

        return new self(
            items: $data['items'] ?? [],
            pagination: $pagination,
            hasSearch: $data['hasSearch'] ?? false,
            activeFiltersCount: $data['activeFiltersCount'] ?? 0,
            hasActiveFilters: $data['hasActiveFilters'] ?? false,
            cacheKey: $data['cacheKey'] ?? '',
            appliedFilters: $data['appliedFilters'] ?? [],
            activeFilters: $data['activeFilters'] ?? [],
            isLoading: $data['isLoading'] ?? false,
            error: $data['error'] ?? null,
            status: $data['status'] ?? 'success',
            timestamp: $data['timestamp'] ?? null,
            availableFilters: $data['availableFilters'] ?? [],
            filterOptions: $data['filterOptions'] ?? [],
        );
    }

    /**
     * Имплементация Arrayable
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'pagination' => $this->pagination->toArray(),
            'hasSearch' => $this->hasSearch,
            'activeFiltersCount' => $this->activeFiltersCount,
            'hasActiveFilters' => $this->hasActiveFilters,
            'cacheKey' => $this->cacheKey,
            'appliedFilters' => $this->appliedFilters,
            'activeFilters' => $this->activeFilters,
            'isLoading' => $this->isLoading,
            'error' => $this->error,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'availableFilters' => $this->availableFilters,
            'filterOptions' => $this->filterOptions,
        ];
    }

    /**
     * Имплементация Jsonable
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toApiResponse(), $options);
    }
}