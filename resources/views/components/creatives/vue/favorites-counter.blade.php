@props([
'initialCount' => 31,
'translations' => [],
'enableAnimation' => true,
'showLoader' => true
])

{{-- Vue островок для интерактивного счетчика избранного --}}
<div data-vue-component="FavoritesCounter" data-vue-props="{{ json_encode([
        'initialCount' => $initialCount,
        'translations' => $translations,
        'enableAnimation' => $enableAnimation,
        'showLoader' => $showLoader
    ]) }}" class="vue-favorites-counter-container">
    {{-- Placeholder пока Vue компонент не загрузился --}}
    <span data-vue-placeholder class="btn__count favorites-counter-placeholder" style="opacity: 0.7;">
        {{ $initialCount }}
    </span>
</div>