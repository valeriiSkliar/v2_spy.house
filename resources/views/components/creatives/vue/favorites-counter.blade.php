@props([
'initialCount' => 31,
'translations' => [],
'enableAnimation' => true,
'showLoader' => true
])

{{-- Vue island for interactive favorites counter --}}
<div data-vue-component="FavoritesCounter" data-vue-props="{{ json_encode([
        'initialCount' => $initialCount,
        'translations' => $translations,
        'enableAnimation' => $enableAnimation,
        'showLoader' => $showLoader
    ]) }}" class="vue-favorites-counter-container">
    {{-- Placeholder until Vue component is loaded --}}
    <span data-vue-placeholder class="btn__count favorites-counter-placeholder" style="opacity: 0.7;">
        {{ $initialCount }}
    </span>
</div>