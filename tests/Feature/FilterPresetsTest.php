<?php

namespace Tests\Feature;

use App\Models\FilterPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterPresetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_empty_presets_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/creatives/filter-presets');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    }

    public function test_user_can_create_filter_preset(): void
    {
        $filters = [
            'searchKeyword' => 'test',
            'countries' => ['US', 'DE'],
            'onlyAdult' => true,
            'activeTab' => 'facebook',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/creatives/filter-presets', [
                'name' => 'My Test Preset',
                'filters' => $filters,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'filters',
                    'has_active_filters',
                    'active_filters_count',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('filter_presets', [
            'user_id' => $this->user->id,
            'name' => 'My Test Preset',
        ]);

        // Проверяем что дефолтные значения не сохраняются
        $preset = FilterPreset::where('user_id', $this->user->id)->first();
        $savedFilters = $preset->filters;

        // searchKeyword и countries должны быть сохранены (не дефолтные)
        $this->assertArrayHasKey('searchKeyword', $savedFilters);
        $this->assertArrayHasKey('countries', $savedFilters);
        $this->assertArrayHasKey('onlyAdult', $savedFilters);
        $this->assertArrayHasKey('activeTab', $savedFilters);

        // dateCreation не должно быть сохранено (дефолтное значение)
        $this->assertArrayNotHasKey('dateCreation', $savedFilters);
    }

    public function test_user_can_get_specific_preset(): void
    {
        $preset = FilterPreset::createPreset($this->user->id, 'Test Preset', [
            'searchKeyword' => 'test search',
            'countries' => ['US'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/creatives/filter-presets/{$preset->id}");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $preset->id,
                    'name' => 'Test Preset',
                ],
            ]);
    }

    public function test_user_can_update_preset(): void
    {
        $preset = FilterPreset::createPreset($this->user->id, 'Original Name', [
            'searchKeyword' => 'original',
        ]);

        $newFilters = [
            'searchKeyword' => 'updated search',
            'onlyAdult' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/creatives/filter-presets/{$preset->id}", [
                'name' => 'Updated Name',
                'filters' => $newFilters,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $preset->id,
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('filter_presets', [
            'id' => $preset->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_delete_preset(): void
    {
        $preset = FilterPreset::createPreset($this->user->id, 'To Delete', [
            'searchKeyword' => 'test',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/creatives/filter-presets/{$preset->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.deleted_id', $preset->id);

        $this->assertDatabaseMissing('filter_presets', [
            'id' => $preset->id,
        ]);
    }

    public function test_user_cannot_access_other_users_presets(): void
    {
        $otherUser = User::factory()->create();
        $preset = FilterPreset::createPreset($otherUser->id, 'Other User Preset', [
            'searchKeyword' => 'secret',
        ]);

        // Попытка получить пресет другого пользователя
        $response = $this->actingAs($this->user)
            ->getJson("/api/creatives/filter-presets/{$preset->id}");

        $response->assertNotFound();

        // Попытка обновить пресет другого пользователя
        $response = $this->actingAs($this->user)
            ->putJson("/api/creatives/filter-presets/{$preset->id}", [
                'name' => 'Hacked Name',
                'filters' => [],
            ]);

        $response->assertNotFound();

        // Попытка удалить пресет другого пользователя
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/creatives/filter-presets/{$preset->id}");

        $response->assertNotFound();
    }

    public function test_preset_name_must_be_unique_per_user(): void
    {
        // Создаем первый пресет
        FilterPreset::createPreset($this->user->id, 'Unique Name', [
            'searchKeyword' => 'first',
        ]);

        // Попытка создать пресет с тем же именем
        $response = $this->actingAs($this->user)
            ->postJson('/api/creatives/filter-presets', [
                'name' => 'Unique Name',
                'filters' => ['searchKeyword' => 'second'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_access_presets(): void
    {
        $response = $this->getJson('/api/creatives/filter-presets');
        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);

        $response = $this->postJson('/api/creatives/filter-presets', [
            'name' => 'Test',
            'filters' => [],
        ]);
        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    public function test_preset_validation_requires_name_and_filters(): void
    {
        // Без имени
        $response = $this->actingAs($this->user)
            ->postJson('/api/creatives/filter-presets', [
                'filters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Без фильтров
        $response = $this->actingAs($this->user)
            ->postJson('/api/creatives/filter-presets', [
                'name' => 'Test Preset',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['filters']);
    }

    public function test_model_sanitizes_filters_correctly(): void
    {
        $filters = [
            'searchKeyword' => 'test',
            'countries' => ['US'],
            'dateCreation' => 'default', // должно быть удалено
            'sortBy' => 'default', // должно быть удалено
            'onlyAdult' => false, // должно быть удалено
            'invalidField' => 'should be removed', // должно быть удалено
        ];

        $preset = FilterPreset::createPreset($this->user->id, 'Test Sanitization', $filters);

        $savedFilters = $preset->filters;

        // Проверяем что сохранились только не-дефолтные значения
        $this->assertArrayHasKey('searchKeyword', $savedFilters);
        $this->assertArrayHasKey('countries', $savedFilters);

        // Проверяем что дефолтные значения удалены
        $this->assertArrayNotHasKey('dateCreation', $savedFilters);
        $this->assertArrayNotHasKey('sortBy', $savedFilters);
        $this->assertArrayNotHasKey('onlyAdult', $savedFilters);

        // Проверяем что недопустимые поля удалены
        $this->assertArrayNotHasKey('invalidField', $savedFilters);
    }

    public function test_model_provides_filters_with_defaults(): void
    {
        $preset = FilterPreset::createPreset($this->user->id, 'Test Defaults', [
            'searchKeyword' => 'test',
            'onlyAdult' => true,
        ]);

        $filtersWithDefaults = $preset->getFiltersWithDefaults();

        // Проверяем что сохраненные значения присутствуют
        $this->assertEquals('test', $filtersWithDefaults['searchKeyword']);
        $this->assertTrue($filtersWithDefaults['onlyAdult']);

        // Проверяем что дефолтные значения добавлены
        $this->assertEquals('default', $filtersWithDefaults['dateCreation']);
        $this->assertEquals([], $filtersWithDefaults['countries']);
        $this->assertEquals(12, $filtersWithDefaults['perPage']);
    }
}
