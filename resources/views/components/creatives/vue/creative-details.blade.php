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
    {{-- Placeholder не нужен, так как компонент скрыт по умолчанию через CSS --}}
    {{-- Компонент отображается только когда store.isDetailsVisible = true --}}
</div>