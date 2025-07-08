<?php

namespace Tests\Unit\DTOs;

use App\Http\DTOs\FilterOptionDTO;
use Tests\TestCase;

class FilterOptionDTOTest extends TestCase
{
    public function test_can_create_basic_option()
    {
        $option = new FilterOptionDTO(
            value: 'us',
            label: 'United States'
        );

        $this->assertEquals('us', $option->value);
        $this->assertEquals('United States', $option->label);
        $this->assertFalse($option->disabled);
        $this->assertFalse($option->selected);
        $this->assertNull($option->description);
        $this->assertNull($option->icon);
        $this->assertNull($option->group);
        $this->assertNull($option->count);
        $this->assertEmpty($option->metadata);
        $this->assertEmpty($option->children);
    }

    public function test_can_create_from_array()
    {
        $data = [
            'value' => 'facebook',
            'label' => 'Facebook',
            'selected' => true,
            'count' => 1500,
            'icon' => 'fab fa-facebook',
            'description' => 'Facebook advertising network',
            'group' => 'social',
            'metadata' => ['type' => 'social_network'],
        ];

        $option = FilterOptionDTO::fromArray($data);

        $this->assertEquals('facebook', $option->value);
        $this->assertEquals('Facebook', $option->label);
        $this->assertTrue($option->selected);
        $this->assertEquals(1500, $option->count);
        $this->assertEquals('fab fa-facebook', $option->icon);
        $this->assertEquals('Facebook advertising network', $option->description);
        $this->assertEquals('social', $option->group);
        $this->assertEquals(['type' => 'social_network'], $option->metadata);
    }

    public function test_factory_methods()
    {
        // Simple option
        $simple = FilterOptionDTO::simple('test', 'Test Option', true);
        $this->assertEquals('test', $simple->value);
        $this->assertEquals('Test Option', $simple->label);
        $this->assertTrue($simple->selected);

        // Option with count
        $withCount = FilterOptionDTO::withCount('ads', 'Advertisements', 250);
        $this->assertEquals('ads', $withCount->value);
        $this->assertEquals('Advertisements', $withCount->label);
        $this->assertEquals(250, $withCount->count);

        // Option with icon
        $withIcon = FilterOptionDTO::withIcon('google', 'Google', 'fab fa-google');
        $this->assertEquals('google', $withIcon->value);
        $this->assertEquals('Google', $withIcon->label);
        $this->assertEquals('fab fa-google', $withIcon->icon);

        // Grouped option
        $grouped = FilterOptionDTO::grouped('push', 'Push Notifications', 'notification_types');
        $this->assertEquals('push', $grouped->value);
        $this->assertEquals('Push Notifications', $grouped->label);
        $this->assertEquals('notification_types', $grouped->group);
    }

    public function test_collection_methods()
    {
        $items = [
            ['value' => 'option1', 'label' => 'Option 1'],
            ['value' => 'option2', 'label' => 'Option 2', 'selected' => true],
            ['value' => 'option3', 'label' => 'Option 3', 'count' => 100],
        ];

        $collection = FilterOptionDTO::collection($items);

        $this->assertCount(3, $collection);
        $this->assertInstanceOf(FilterOptionDTO::class, $collection[0]);
        $this->assertEquals('option1', $collection[0]->value);
        $this->assertTrue($collection[1]->selected);
        $this->assertEquals(100, $collection[2]->count);

        // Simple collection
        $simpleItems = ['val1', 'val2', 'val3'];
        $simpleCollection = FilterOptionDTO::simpleCollection($simpleItems);

        $this->assertCount(3, $simpleCollection);
        $this->assertEquals('val1', $simpleCollection[0]->value);
        $this->assertEquals('val1', $simpleCollection[0]->label);
    }

    public function test_countries_options()
    {
        $countries = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
        ];

        $options = FilterOptionDTO::countries($countries, ['US']);

        $this->assertCount(3, $options); // Возвращаем все страны из входного массива

        // Проверяем что метод возвращает все страны в том же порядке
        $this->assertEquals('US', $options[0]->value);
        $this->assertEquals('United States', $options[0]->label);
        $this->assertTrue($options[0]->selected);

        $this->assertEquals('GB', $options[1]->value);
        $this->assertEquals('United Kingdom', $options[1]->label);
        $this->assertFalse($options[1]->selected);
    }

    public function test_countries_options_with_array_values()
    {
        // Test case when countries helper returns arrays instead of strings
        $countries = [
            'US' => ['name' => 'United States', 'active' => true],
            'GB' => ['name' => 'United Kingdom', 'active' => true],
            'DE' => ['label' => 'Germany', 'active' => false],
            'FR' => ['code' => 'FR'], // no name or label - should fallback to code
        ];

        $options = FilterOptionDTO::countries($countries, ['US']);

        $this->assertCount(4, $options); // Возвращаем все страны из входного массива

        $this->assertEquals('US', $options[0]->value);
        $this->assertEquals('United States', $options[0]->label);
        $this->assertTrue($options[0]->selected);

        $this->assertEquals('GB', $options[1]->value);
        $this->assertEquals('United Kingdom', $options[1]->label);
        $this->assertFalse($options[1]->selected);

        $this->assertEquals('DE', $options[2]->value);
        $this->assertEquals('Germany', $options[2]->label);
        $this->assertFalse($options[2]->selected);

        $this->assertEquals('FR', $options[3]->value);
        $this->assertEquals('FR', $options[3]->label); // fallback to code
        $this->assertFalse($options[3]->selected);
    }

    public function test_languages_options()
    {
        $languages = [
            'en' => 'English',
            'ru' => 'Russian',
            'de' => 'German',
        ];

        $selectedLanguages = ['en', 'ru'];
        $options = FilterOptionDTO::languages($languages, $selectedLanguages);

        $this->assertCount(3, $options);
        $this->assertEquals('en', $options[0]->value);
        $this->assertTrue($options[0]->selected);
        $this->assertTrue($options[1]->selected);
        $this->assertFalse($options[2]->selected);
    }

    public function test_languages_options_with_array_values()
    {
        // Test case when languages helper returns arrays instead of strings
        $languages = [
            'en' => ['name' => 'English', 'native' => 'English'],
            'ru' => ['name' => 'Russian', 'native' => 'Русский'],
            'de' => ['label' => 'German'],
            'fr' => ['code' => 'fr'], // no name or label - should fallback to code
        ];

        $selectedLanguages = ['en', 'ru'];
        $options = FilterOptionDTO::languages($languages, $selectedLanguages);

        $this->assertCount(4, $options);

        $this->assertEquals('en', $options[0]->value);
        $this->assertEquals('English', $options[0]->label);
        $this->assertTrue($options[0]->selected);

        $this->assertEquals('ru', $options[1]->value);
        $this->assertEquals('Russian', $options[1]->label);
        $this->assertTrue($options[1]->selected);

        $this->assertEquals('de', $options[2]->value);
        $this->assertEquals('German', $options[2]->label);
        $this->assertFalse($options[2]->selected);

        $this->assertEquals('fr', $options[3]->value);
        $this->assertEquals('fr', $options[3]->label); // fallback to code
        $this->assertFalse($options[3]->selected);
    }

    public function test_sort_options()
    {
        $options = FilterOptionDTO::sortOptions(['byCreationDate']);

        $this->assertCount(3, $options);

        $creationOption = $options[0];
        $this->assertEquals('byCreationDate', $creationOption->value);
        $this->assertEquals('По дате создания', $creationOption->label);
        $this->assertTrue($creationOption->selected);

        $activityOption = $options[1];
        $this->assertEquals('byActivity', $activityOption->value);
        $this->assertFalse($activityOption->selected);

        $popularityOption = $options[2];
        $this->assertEquals('byPopularity', $popularityOption->value);
        $this->assertFalse($popularityOption->selected);
    }

    public function test_date_range_options()
    {
        $options = FilterOptionDTO::dateRangeOptions('last7');

        $this->assertCount(10, $options);

        // Проверяем выбранную опцию
        $selectedOption = array_filter($options, fn($opt) => $opt->selected);
        $this->assertCount(1, $selectedOption);

        $selected = array_values($selectedOption)[0];
        $this->assertEquals('last7', $selected->value);
        $this->assertEquals('За последние 7 дней', $selected->label);

        // Проверяем первую опцию
        $this->assertEquals('default', $options[0]->value);
        $this->assertEquals('Все время', $options[0]->label);
        $this->assertFalse($options[0]->selected);
    }

    public function test_per_page_options()
    {
        $options = FilterOptionDTO::perPageOptions(24);

        $this->assertCount(5, $options);
        $this->assertEquals('6', $options[0]->value);
        $this->assertEquals('12', $options[1]->value);
        $this->assertEquals('24', $options[2]->value);
        $this->assertTrue($options[2]->selected);
        $this->assertEquals('48', $options[3]->value);
        $this->assertEquals('96', $options[4]->value);
    }

    public function test_advertising_networks_with_count()
    {
        $networks = [
            ['value' => 'facebook', 'label' => 'Facebook', 'logo' => 'https://example.com/facebook.png'],
            ['value' => 'google', 'label' => 'Google Ads', 'logo' => 'https://example.com/google.png'],
            'tiktok' // простая строка
        ];

        $selectedNetworks = ['facebook'];
        $counts = ['facebook' => 1500, 'google' => 2300, 'tiktok' => 800];

        $options = FilterOptionDTO::advertisingNetworksWithCount($networks, $selectedNetworks, $counts);

        $this->assertCount(3, $options);

        $this->assertEquals('facebook', $options[0]->value);
        $this->assertEquals('Facebook', $options[0]->label);
        $this->assertTrue($options[0]->selected);
        $this->assertEquals(1500, $options[0]->count);
        $this->assertEquals('https://example.com/facebook.png', $options[0]->logo);

        $this->assertEquals('google', $options[1]->value);
        $this->assertEquals('Google Ads', $options[1]->label);
        $this->assertFalse($options[1]->selected);
        $this->assertEquals(2300, $options[1]->count);
        $this->assertEquals('https://example.com/google.png', $options[1]->logo);

        $this->assertEquals('tiktok', $options[2]->value);
        $this->assertEquals('tiktok', $options[2]->label);
        $this->assertEquals(800, $options[2]->count);
        $this->assertNull($options[2]->logo);
    }

    public function test_advertising_networks_with_collection()
    {
        // Test with Laravel Collection
        $networks = collect([
            ['name' => 'Facebook', 'code' => 'facebook', 'logo' => 'https://example.com/facebook.png'],
            ['name' => 'Google Ads', 'code' => 'google', 'logo' => 'https://example.com/google.png'],
            ['label' => 'TikTok', 'value' => 'tiktok', 'logo' => 'https://example.com/tiktok.png']
        ]);

        $selectedNetworks = ['facebook'];
        $counts = ['facebook' => 1500, 'google' => 2300, 'tiktok' => 800];

        $options = FilterOptionDTO::advertisingNetworksWithCount($networks, $selectedNetworks, $counts);

        $this->assertCount(3, $options);

        $this->assertEquals('facebook', $options[0]->value);
        $this->assertEquals('Facebook', $options[0]->label);
        $this->assertTrue($options[0]->selected);
        $this->assertEquals('https://example.com/facebook.png', $options[0]->logo);

        $this->assertEquals('google', $options[1]->value);
        $this->assertEquals('Google Ads', $options[1]->label);
        $this->assertFalse($options[1]->selected);
        $this->assertEquals('https://example.com/google.png', $options[1]->logo);

        $this->assertEquals('tiktok', $options[2]->value);
        $this->assertEquals('TikTok', $options[2]->label);
        $this->assertEquals('https://example.com/tiktok.png', $options[2]->logo);
    }

    public function test_image_size_options()
    {
        $selectedSizes = ['16x9', '1x1'];
        $options = FilterOptionDTO::imageSizeOptions($selectedSizes);

        $this->assertCount(8, $options);

        $selectedOptions = array_filter($options, fn($opt) => $opt->selected);
        $this->assertCount(2, $selectedOptions);

        $this->assertEquals('1x1', $options[0]->value);
        $this->assertEquals('1x1 (Square)', $options[0]->label);
        $this->assertTrue($options[0]->selected);

        $this->assertEquals('16x9', $options[1]->value);
        $this->assertEquals('16x9 (Landscape)', $options[1]->label);
        $this->assertTrue($options[1]->selected);
    }

    public function test_utility_methods()
    {
        $options = [
            FilterOptionDTO::simple('opt1', 'Option 1', true),
            FilterOptionDTO::simple('opt2', 'Option 2', false),
            FilterOptionDTO::withCount('opt3', 'Option 3', 100),
        ];

        // Test groupOptions (пустые группы)
        $grouped = FilterOptionDTO::groupOptions($options);
        $this->assertArrayHasKey('default', $grouped);
        $this->assertCount(3, $grouped['default']);

        // Test findByValue
        $found = FilterOptionDTO::findByValue($options, 'opt2');
        $this->assertNotNull($found);
        $this->assertEquals('Option 2', $found->label);

        $notFound = FilterOptionDTO::findByValue($options, 'nonexistent');
        $this->assertNull($notFound);

        // Test getSelected
        $selected = FilterOptionDTO::getSelected($options);
        $this->assertCount(1, $selected);
        $this->assertEquals('opt1', $selected[0]->value);

        // Test getValues
        $values = FilterOptionDTO::getValues($options);
        $this->assertEquals(['opt1', 'opt2', 'opt3'], $values);

        // Test getLabels
        $labels = FilterOptionDTO::getLabels($options);
        $this->assertEquals(['Option 1', 'Option 2', 'Option 3'], $labels);
    }

    public function test_set_selected()
    {
        $options = [
            FilterOptionDTO::simple('opt1', 'Option 1', true),
            FilterOptionDTO::simple('opt2', 'Option 2', false),
            FilterOptionDTO::simple('opt3', 'Option 3', false),
        ];

        $updatedOptions = FilterOptionDTO::setSelected($options, ['opt2', 'opt3']);

        $this->assertFalse($updatedOptions[0]->selected);
        $this->assertTrue($updatedOptions[1]->selected);
        $this->assertTrue($updatedOptions[2]->selected);
    }

    public function test_sort_collection_method()
    {
        $options = [
            FilterOptionDTO::withCount('c', 'C Option', 100),
            FilterOptionDTO::withCount('a', 'A Option', 300),
            FilterOptionDTO::withCount('b', 'B Option', 200),
        ];

        // Sort by label
        $sortedByLabel = FilterOptionDTO::sortCollection($options, 'label');
        $this->assertEquals('A Option', $sortedByLabel[0]->label);
        $this->assertEquals('B Option', $sortedByLabel[1]->label);
        $this->assertEquals('C Option', $sortedByLabel[2]->label);

        // Sort by value
        $sortedByValue = FilterOptionDTO::sortCollection($options, 'value');
        $this->assertEquals('a', $sortedByValue[0]->value);
        $this->assertEquals('b', $sortedByValue[1]->value);
        $this->assertEquals('c', $sortedByValue[2]->value);

        // Sort by count (descending)
        $sortedByCount = FilterOptionDTO::sortCollection($options, 'count');
        $this->assertEquals(300, $sortedByCount[0]->count);
        $this->assertEquals(200, $sortedByCount[1]->count);
        $this->assertEquals(100, $sortedByCount[2]->count);
    }

    public function test_filter_options()
    {
        $options = [
            FilterOptionDTO::simple('active', 'Active Option'),
            FilterOptionDTO::simple('disabled', 'Disabled Option')->disable(),
            FilterOptionDTO::withCount('popular', 'Popular Option', 1000),
        ];

        // Filter active options
        $activeOptions = FilterOptionDTO::filterOptions($options, fn($opt) => !$opt->disabled);
        $this->assertCount(2, $activeOptions);

        // Filter options with count > 500
        $popularOptions = FilterOptionDTO::filterOptions($options, fn($opt) => ($opt->count ?? 0) > 500);
        $this->assertCount(1, $popularOptions);
        $this->assertEquals('popular', $popularOptions[2]->value);
    }

    public function test_fluent_interface()
    {
        $option = FilterOptionDTO::simple('test', 'Test Option');

        // Test method chaining
        $result = $option
            ->select()
            ->setCount(50)
            ->withMetadata(['category' => 'testing']);

        $this->assertTrue($result->selected);
        $this->assertEquals(50, $result->count);
        $this->assertEquals(['category' => 'testing'], $result->metadata);

        // Test disable/enable
        $option->disable();
        $this->assertTrue($option->disabled);
        $this->assertFalse($option->isActive());

        $option->enable();
        $this->assertFalse($option->disabled);
        $this->assertTrue($option->isActive());

        // Test deselect
        $option->deselect();
        $this->assertFalse($option->selected);
        $this->assertFalse($option->isSelected());
    }

    public function test_children_support()
    {
        $childrenData = [
            ['value' => 'child1', 'label' => 'Child 1'],
            ['value' => 'child2', 'label' => 'Child 2'],
        ];

        $parentOption = FilterOptionDTO::fromArray([
            'value' => 'parent',
            'label' => 'Parent Option',
            'children' => $childrenData,
        ]);

        $this->assertTrue($parentOption->hasChildren());
        $this->assertCount(2, $parentOption->children);
        $this->assertInstanceOf(FilterOptionDTO::class, $parentOption->children[0]);
        $this->assertEquals('child1', $parentOption->children[0]->value);
    }

    public function test_clone_method()
    {
        $original = FilterOptionDTO::withCount('original', 'Original Option', 100);
        $clone = $original->clone(['label' => 'Cloned Option', 'count' => 200]);

        $this->assertEquals('original', $original->value);
        $this->assertEquals('Original Option', $original->label);
        $this->assertEquals(100, $original->count);

        $this->assertEquals('original', $clone->value);
        $this->assertEquals('Cloned Option', $clone->label);
        $this->assertEquals(200, $clone->count);
    }

    public function test_array_conversion_methods()
    {
        $option = FilterOptionDTO::fromArray([
            'value' => 'test',
            'label' => 'Test Option',
            'selected' => true,
            'count' => 150,
            'icon' => 'test-icon',
            'logo' => 'https://example.com/logo.png',
            'description' => 'Test description',
        ]);

        // toArray
        $array = $option->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('selected', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertArrayHasKey('logo', $array);
        $this->assertEquals('test', $array['value']);
        $this->assertTrue($array['selected']);
        $this->assertEquals('https://example.com/logo.png', $array['logo']);

        // toCompactArray
        $compact = $option->toCompactArray();
        $this->assertArrayHasKey('value', $compact);
        $this->assertArrayHasKey('label', $compact);
        $this->assertArrayHasKey('selected', $compact);
        $this->assertArrayHasKey('count', $compact);
        $this->assertArrayHasKey('icon', $compact);
        $this->assertArrayHasKey('logo', $compact);
        $this->assertEquals('https://example.com/logo.png', $compact['logo']);
        // Не должно включать disabled если оно false
        $this->assertArrayNotHasKey('disabled', $compact);

        // toSelectFormat
        $selectFormat = $option->toSelectFormat();
        $this->assertEquals([
            'value' => 'test',
            'text' => 'Test Option',
            'disabled' => false,
            'selected' => true,
        ], $selectFormat);

        // toJson
        $json = $option->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);
    }

    public function test_validation()
    {
        // Valid data
        $validData = [
            'value' => 'test',
            'label' => 'Test Option',
            'disabled' => false,
            'selected' => true,
            'count' => 100,
            'metadata' => ['key' => 'value'],
        ];

        $errors = FilterOptionDTO::validate($validData);
        $this->assertEmpty($errors);

        // Invalid data
        $invalidData = [
            'value' => '', // empty
            'label' => '', // empty
            'disabled' => 'not-boolean',
            'selected' => 'not-boolean',
            'count' => -1,
            'sortOrder' => -5,
            'metadata' => 'not-array',
            'children' => 'not-array',
        ];

        $errors = FilterOptionDTO::validate($invalidData);
        $this->assertNotEmpty($errors);
        $this->assertContains('value is required', $errors);
        $this->assertContains('label is required', $errors);
        $this->assertContains('disabled must be boolean', $errors);
        $this->assertContains('selected must be boolean', $errors);
        $this->assertContains('count must be non-negative integer', $errors);
        $this->assertContains('sortOrder must be non-negative integer', $errors);
        $this->assertContains('metadata must be array', $errors);
        $this->assertContains('children must be array', $errors);
    }

    public function test_from_array_with_validation()
    {
        $validData = [
            'value' => 'test',
            'label' => 'Test Option',
        ];

        $option = FilterOptionDTO::fromArrayWithValidation($validData);
        $this->assertEquals('test', $option->value);
        $this->assertEquals('Test Option', $option->label);

        // Test validation failure
        $invalidData = [
            'value' => '',
            'label' => 'Test',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed:');

        FilterOptionDTO::fromArrayWithValidation($invalidData);
    }

    public function test_group_options_with_actual_groups()
    {
        $options = [
            FilterOptionDTO::grouped('facebook', 'Facebook', 'social'),
            FilterOptionDTO::grouped('google', 'Google', 'search'),
            FilterOptionDTO::grouped('twitter', 'Twitter', 'social'),
            FilterOptionDTO::simple('other', 'Other Option'), // no group
        ];

        $grouped = FilterOptionDTO::groupOptions($options);

        $this->assertArrayHasKey('social', $grouped);
        $this->assertArrayHasKey('search', $grouped);
        $this->assertArrayHasKey('default', $grouped);

        $this->assertCount(2, $grouped['social']);
        $this->assertCount(1, $grouped['search']);
        $this->assertCount(1, $grouped['default']);

        $this->assertEquals('facebook', $grouped['social'][0]->value);
        $this->assertEquals('twitter', $grouped['social'][1]->value);
    }
}
