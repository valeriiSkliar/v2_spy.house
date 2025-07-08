# FilterOptionDTO Implementation

This document describes the `FilterOptionDTO` implementation that provides standardized options for select components used in the creatives filtering system.

## Overview

The `FilterOptionDTO` class standardizes the structure for dropdown/select options, ensuring consistency across all filter components. It integrates seamlessly with the existing DTO architecture and provides extensive functionality for managing select options.

## Key Features

### 🎯 **Core Functionality**

- **Standardized Structure**: Consistent `value`/`label` pattern for all select options
- **Rich Metadata**: Support for icons, descriptions, counts, groups, and colors
- **Hierarchical Support**: Parent-child relationships for nested options
- **State Management**: Selected/disabled states with fluent interface
- **Type Safety**: Full validation and type checking

### 🏭 **Factory Methods**

```php
// Simple options
FilterOptionDTO::simple('us', 'United States', true);

// Options with count
FilterOptionDTO::withCount('facebook', 'Facebook', 1500000);

// Options with icon
FilterOptionDTO::withIcon('google', 'Google', 'fab fa-google');

// Grouped options
FilterOptionDTO::grouped('push', 'Push Notifications', 'notification_types');
```

### 🌍 **Specialized Options**

```php
// Countries with "All countries" option
FilterOptionDTO::countries($countries, $selectedCountry);

// Languages with multi-select support
FilterOptionDTO::languages($languages, $selectedLanguages);

// Sort options
FilterOptionDTO::sortOptions($selectedSort);

// Date ranges
FilterOptionDTO::dateRangeOptions($selectedRange);

// Per page options
FilterOptionDTO::perPageOptions($selectedPerPage);

// Image sizes
FilterOptionDTO::imageSizeOptions($selectedSizes);

// Advertising networks with counts
FilterOptionDTO::advertisingNetworksWithCount($networks, $selected, $counts);
```

## Structure

### Basic Option Structure
```php
[
    'value' => 'us',
    'label' => 'United States',
    'disabled' => false,
    'selected' => true,
    'description' => 'United States of America',
    'icon' => 'flag-us',
    'group' => 'north_america',
    'count' => 1500000,
    'metadata' => ['iso' => 'US', 'currency' => 'USD'],
    'color' => '#007bff',
    'sortOrder' => 1,
    'children' => [],
    'parentValue' => null
]
```

### Compact Format (for mobile)
```php
[
    'value' => 'us',
    'label' => 'United States',
    'selected' => true,
    'count' => 1500000,
    'icon' => 'flag-us'
]
```

### Vue/React Select Format
```php
[
    'value' => 'us',
    'text' => 'United States',
    'disabled' => false,
    'selected' => true
]
```

## Integration with Controllers

### Updated BaseCreativesController

The controller now uses `FilterOptionDTO` for all select options:

```php
public function getSelectOptions(CreativesFiltersDTO $filtersDTO = null)
{
    return [
        'countries' => array_map(
            fn($option) => $option->toArray(),
            FilterOptionDTO::countries(
                IsoCodesHelper::getAllCountries(app()->getLocale()),
                $filtersDTO->country
            )
        ),
        'sortOptions' => array_map(
            fn($option) => $option->toArray(),
            FilterOptionDTO::sortOptions([$filtersDTO->sortBy])
        ),
        'advertisingNetworks' => array_map(
            fn($option) => $option->toArray(),
            FilterOptionDTO::advertisingNetworksWithCount(
                AdvertismentNetwork::forCreativeFilters(),
                $filtersDTO->advertisingNetworks,
                $this->getNetworksCounts()
            )
        ),
        // ... other options
    ];
}
```

### New API Endpoint

Added dedicated endpoint for filter options:

```php
/**
 * GET /api/creatives/filter-options
 */
public function getFilterOptionsApi(CreativesRequest $request)
{
    $filtersDTO = CreativesFiltersDTO::fromRequest($request);
    $options = $this->getSelectOptions($filtersDTO);
    
    return response()->json([
        'status' => 'success',
        'data' => $options,
        'meta' => [
            'currentFilters' => $filtersDTO->toArray(),
            'hasActiveFilters' => $filtersDTO->hasActiveFilters(),
            'activeFiltersCount' => $filtersDTO->getActiveFiltersCount(),
        ]
    ]);
}
```

## Utility Methods

### Collection Management
```php
// Create collection from array
$options = FilterOptionDTO::collection($arrayData);

// Simple collection from strings
$options = FilterOptionDTO::simpleCollection(['opt1', 'opt2', 'opt3']);

// Group options by category
$grouped = FilterOptionDTO::groupOptions($options);

// Sort options
$sorted = FilterOptionDTO::sortCollection($options, 'label');

// Filter options
$filtered = FilterOptionDTO::filterOptions($options, fn($opt) => !$opt->disabled);
```

### State Management
```php
// Find option by value
$option = FilterOptionDTO::findByValue($options, 'us');

// Get selected options
$selected = FilterOptionDTO::getSelected($options);

// Set selected values
$updated = FilterOptionDTO::setSelected($options, ['us', 'gb']);

// Get values/labels arrays
$values = FilterOptionDTO::getValues($options);
$labels = FilterOptionDTO::getLabels($options);
```

### Fluent Interface
```php
$option = FilterOptionDTO::simple('test', 'Test Option')
    ->select()
    ->setCount(100)
    ->withMetadata(['category' => 'testing'])
    ->disable();
```

## Benefits

1. **Consistency**: Standardized structure across all select components
2. **Type Safety**: Full validation and type checking
3. **Rich Metadata**: Support for counts, icons, descriptions, and grouping
4. **Frontend Ready**: Multiple output formats for different frameworks
5. **Performance**: Compact formats for mobile optimization
6. **Maintainability**: Clear separation of concerns and extensive testing
7. **Flexibility**: Support for hierarchical options and custom metadata

## Testing

Comprehensive test suite with 22 test methods covering:

- Factory methods and basic creation
- Specialized option types (countries, languages, etc.)
- Collection management and utilities
- State management and selection
- Validation and error handling
- Fluent interface and method chaining
- Array conversion and output formats

**Run tests**:
```bash
php artisan test --filter=FilterOptionDTOTest
```

## Usage Examples

### Country Select
```php
$countries = IsoCodesHelper::getAllCountries(app()->getLocale());
$options = FilterOptionDTO::countries($countries, 'US');

// Frontend receives:
[
    ['value' => 'default', 'label' => 'Все страны', 'selected' => false],
    ['value' => 'US', 'label' => 'United States', 'selected' => true],
    ['value' => 'GB', 'label' => 'United Kingdom', 'selected' => false],
    // ...
]
```

### Advertising Networks with Counts
```php
$networks = [
    ['value' => 'facebook', 'label' => 'Facebook'],
    ['value' => 'google', 'label' => 'Google Ads']
];
$counts = ['facebook' => 1500000, 'google' => 2300000];
$selected = ['facebook'];

$options = FilterOptionDTO::advertisingNetworksWithCount($networks, $selected, $counts);

// Frontend receives:
[
    ['value' => 'facebook', 'label' => 'Facebook', 'count' => 1500000, 'selected' => true],
    ['value' => 'google', 'label' => 'Google Ads', 'count' => 2300000, 'selected' => false]
]
```

### Custom Options with Grouping
```php
$options = [
    FilterOptionDTO::grouped('facebook', 'Facebook', 'social'),
    FilterOptionDTO::grouped('instagram', 'Instagram', 'social'),
    FilterOptionDTO::grouped('google', 'Google Ads', 'search'),
];

$grouped = FilterOptionDTO::groupOptions($options);

// Returns:
[
    'social' => [/* Facebook, Instagram options */],
    'search' => [/* Google Ads option */]
]
```

## Future Enhancements

1. **Caching**: Add option caching for frequently used selects
2. **Internationalization**: Enhanced i18n support for labels
3. **Dynamic Loading**: Support for lazy-loaded options
4. **Search Integration**: Built-in search/filter capabilities
5. **Accessibility**: Enhanced ARIA attributes and screen reader support