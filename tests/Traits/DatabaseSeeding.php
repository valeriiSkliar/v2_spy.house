<?php

namespace Tests\Traits;

use App\Models\Frontend\IsoEntity;

trait DatabaseSeeding
{
    /**
     * Создание базовых тестовых данных для ISO сущностей
     */
    protected function seedIsoEntities(): void
    {
        $this->seedTestCountries();
        $this->seedTestLanguages();
    }

    /**
     * Создание тестовых стран
     */
    protected function seedTestCountries(): void
    {
        $countries = [
            ['US', 'USA', 'United States', true],
            ['CA', 'CAN', 'Canada', true],
            ['GB', 'GBR', 'United Kingdom', true],
            ['FR', 'FRA', 'France', true],
            ['DE', 'DEU', 'Germany', true],
            ['RU', 'RUS', 'Russia', true],
            ['XX', 'XXX', 'Inactive Country', false], // для тестов неактивных стран
        ];

        foreach ($countries as [$iso2, $iso3, $name, $isActive]) {
            IsoEntity::create([
                'type' => 'country',
                'iso_code_2' => $iso2,
                'iso_code_3' => $iso3,
                'name' => $name,
                'is_active' => $isActive,
            ]);
        }
    }

    /**
     * Создание тестовых языков
     */
    protected function seedTestLanguages(): void
    {
        $languages = [
            ['en', 'eng', 'English', true],
            ['ru', 'rus', 'Russian', true],
            ['fr', 'fra', 'French', true],
            ['de', 'deu', 'German', true],
            ['es', 'spa', 'Spanish', true],
            ['it', 'ita', 'Italian', true],
            ['xx', 'xxx', 'Inactive Language', false], // для тестов неактивных языков
        ];

        foreach ($languages as [$iso2, $iso3, $name, $isActive]) {
            IsoEntity::create([
                'type' => 'language',
                'iso_code_2' => $iso2,
                'iso_code_3' => $iso3,
                'name' => $name,
                'is_active' => $isActive,
            ]);
        }
    }

    /**
     * Создание минимального набора тестовых данных
     */
    protected function seedMinimalTestData(): void
    {
        // Только самые необходимые данные для быстрых тестов
        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'name' => 'United States',
            'is_active' => true,
        ]);

        IsoEntity::create([
            'type' => 'language',
            'iso_code_2' => 'en',
            'iso_code_3' => 'eng',
            'name' => 'English',
            'is_active' => true,
        ]);
    }

    /**
     * Очистка данных перед тестами
     */
    protected function cleanupTestData(): void
    {
        // Быстрая очистка без foreign key checks
        IsoEntity::query()->delete();
    }
}
