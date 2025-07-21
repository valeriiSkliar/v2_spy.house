{{--
Компонент деталей креатива (Vue Island)
Интегрируется в общую архитектуру Vue Islands с глобальным Pinia Store
--}}

@props([
'showSimilarCreatives' => false,
'translations' => [],
])

<div data-vue-component="CreativeDetailsComponent" data-vue-props="{{ json_encode([
        'showSimilarCreatives' => $showSimilarCreatives,
        'translations' => $translations,
    ]) }}" class="vue-island-creative-details">
    {{-- Placeholder is not needed, as the component is hidden by default via CSS --}}
    {{-- The component is displayed only when store.isDetailsVisible = true --}}
</div>