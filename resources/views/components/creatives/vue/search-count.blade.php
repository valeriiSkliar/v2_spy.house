@props([
'initialCount' => 0,
'translations' => [],
])

@php
// Подготавливаем переводы для компонента
// Laravel поддерживает формат "singular | plural | genitive" в одной строке
$advertisementsTranslation = __('creatives.advertisements');
$formsArray = explode(' | ', $advertisementsTranslation);

$componentTranslations = array_merge([
'advertisement' => $formsArray[0] ?? 'креатив', // singular
'advertisements' => $formsArray[1] ?? 'креатива', // plural (2-4)
'advertisementsGenitive' => $formsArray[2] ?? 'креативов', // genitive (5+)
], $translations);

// Данные для Vue компонента
$vueProps = [
'initialCount' => $initialCount,
'translations' => $componentTranslations,
];
@endphp

<div data-vue-component="SearchCountComponent" data-vue-props="{{ json_encode($vueProps) }}">
    {{-- Placeholder для SSR --}}
    <div data-vue-placeholder class="mb-20">
        <div class="search-count">
            <span>{{ $initialCount }}</span>
            {{ trans_choice('creatives.advertisements', $initialCount) }}
        </div>
    </div>
</div>