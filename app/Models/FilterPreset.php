<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FilterPreset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope для фильтрации пресетов текущего пользователя
     */
    public function scopeForCurrentUser($query)
    {
        $userId = Auth::id();
        if (!$userId) {
            return $query->whereRaw('1 = 0'); // Возвращаем пустой результат для неаутентифицированных пользователей
        }

        return $query->where('user_id', $userId);
    }

    /**
     * Scope для фильтрации пресетов конкретного пользователя
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Создать пресет фильтров для пользователя
     */
    public static function createPreset(int $userId, string $name, array $filters): self
    {
        // Проверяем уникальность имени для данного пользователя
        $existingPreset = self::where('user_id', $userId)
            ->where('name', $name)
            ->first();

        if ($existingPreset) {
            throw new \InvalidArgumentException("Preset with name '{$name}' already exists for this user");
        }

        // Валидируем и очищаем фильтры
        $cleanFilters = self::sanitizeFilters($filters);

        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'filters' => $cleanFilters,
        ]);
    }

    /**
     * Обновить существующий пресет
     */
    public function updatePreset(string $name, array $filters): bool
    {
        // Проверяем уникальность имени (исключая текущий пресет)
        $existingPreset = self::where('user_id', $this->user_id)
            ->where('name', $name)
            ->where('id', '!=', $this->id)
            ->first();

        if ($existingPreset) {
            throw new \InvalidArgumentException("Preset with name '{$name}' already exists for this user");
        }

        // Валидируем и очищаем фильтры
        $cleanFilters = self::sanitizeFilters($filters);

        return $this->update([
            'name' => $name,
            'filters' => $cleanFilters,
        ]);
    }

    /**
     * Получить все пресеты пользователя в формате для селекта
     */
    public static function getPresetsForSelect(int $userId): array
    {
        return self::forUser($userId)
            ->orderBy('name')
            ->get()
            ->map(function ($preset) {
                return [
                    'value' => $preset->id,
                    'label' => $preset->name,
                    'filters' => $preset->filters,
                    'created_at' => $preset->created_at->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Валидировать и очистить фильтры
     * Удаляет служебные поля и валидирует структуру
     */
    protected static function sanitizeFilters(array $filters): array
    {
        // Список разрешенных полей фильтров
        $allowedFields = [
            'searchKeyword',
            'countries',
            'dateCreation',
            'sortBy',
            'periodDisplay',
            'advertisingNetworks',
            'languages',
            'operatingSystems',
            'browsers',
            'devices',
            'imageSizes',
            'onlyAdult',
            'perPage',
            'activeTab',
        ];

        // Фильтруем только разрешенные поля
        $cleanFilters = array_intersect_key($filters, array_flip($allowedFields));

        // Удаляем поля со значениями по умолчанию для экономии места
        $defaultValues = [
            'searchKeyword' => '',
            'countries' => [],
            'dateCreation' => 'default',
            'sortBy' => 'default',
            'periodDisplay' => 'default',
            'advertisingNetworks' => [],
            'languages' => [],
            'operatingSystems' => [],
            'browsers' => [],
            'devices' => [],
            'imageSizes' => [],
            'onlyAdult' => false,
            'perPage' => 12,
            'activeTab' => 'push',
        ];

        foreach ($defaultValues as $field => $defaultValue) {
            if (isset($cleanFilters[$field]) && $cleanFilters[$field] === $defaultValue) {
                unset($cleanFilters[$field]);
            }
        }

        return $cleanFilters;
    }

    /**
     * Применить фильтры из пресета с объединением с дефолтными значениями
     */
    public function getFiltersWithDefaults(): array
    {
        $defaultFilters = [
            'searchKeyword' => '',
            'countries' => [],
            'dateCreation' => 'default',
            'sortBy' => 'default',
            'periodDisplay' => 'default',
            'advertisingNetworks' => [],
            'languages' => [],
            'operatingSystems' => [],
            'browsers' => [],
            'devices' => [],
            'imageSizes' => [],
            'onlyAdult' => false,
            'perPage' => 12,
            'activeTab' => 'push',
        ];

        return array_merge($defaultFilters, $this->filters);
    }

    /**
     * Проверить, содержит ли пресет активные фильтры
     */
    public function hasActiveFilters(): bool
    {
        $filters = $this->getFiltersWithDefaults();

        // Проверяем наличие значимых фильтров
        return !empty($filters['searchKeyword']) ||
            !empty($filters['countries']) ||
            $filters['dateCreation'] !== 'default' ||
            $filters['sortBy'] !== 'default' ||
            $filters['periodDisplay'] !== 'default' ||
            !empty($filters['advertisingNetworks']) ||
            !empty($filters['languages']) ||
            !empty($filters['operatingSystems']) ||
            !empty($filters['browsers']) ||
            !empty($filters['devices']) ||
            !empty($filters['imageSizes']) ||
            $filters['onlyAdult'] === true ||
            $filters['activeTab'] !== 'push';
    }

    /**
     * Получить количество активных фильтров в пресете
     */
    public function getActiveFiltersCount(): int
    {
        $filters = $this->getFiltersWithDefaults();
        $count = 0;

        if (!empty($filters['searchKeyword'])) $count++;
        if (!empty($filters['countries'])) $count++;
        if ($filters['dateCreation'] !== 'default') $count++;
        if ($filters['sortBy'] !== 'default') $count++;
        if ($filters['periodDisplay'] !== 'default') $count++;
        if (!empty($filters['advertisingNetworks'])) $count++;
        if (!empty($filters['languages'])) $count++;
        if (!empty($filters['operatingSystems'])) $count++;
        if (!empty($filters['browsers'])) $count++;
        if (!empty($filters['devices'])) $count++;
        if (!empty($filters['imageSizes'])) $count++;
        if ($filters['onlyAdult'] === true) $count++;
        if ($filters['activeTab'] !== 'push') $count++;

        return $count;
    }

    /**
     * Преобразовать модель в массив для API ответа
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'filters' => $this->getFiltersWithDefaults(),
            'has_active_filters' => $this->hasActiveFilters(),
            'active_filters_count' => $this->getActiveFiltersCount(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
