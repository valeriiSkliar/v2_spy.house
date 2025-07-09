@props([
'listTranslations' => [],
'perPage' => 12,
'activeTab' => 'push',
'detailsTranslations' => [],
'showSimilarCreatives' => false,
'cardTranslations' => [],
'userData' => [],
])
<div class="vue-component-wrapper" data-vue-component="CreativesListComponent" data-vue-props='{
        "translations": {{ json_encode($listTranslations) }},
        "perPage": {{ $perPage }},
        "activeTab": {{ json_encode($activeTab) }},
        "detailsTranslations": {{ json_encode($detailsTranslations) }},
        "showSimilarCreatives": {{ json_encode($showSimilarCreatives) }},
        "cardTranslations": {{ json_encode($cardTranslations) }},
        "userData": {{ json_encode($userData) }}
    }' data-vue-placeholder-manual>

    <!-- Placeholder карточек креативов (количество = perPage) -->
    <div class="creatives-list" @if ($activeTab==='facebook' || $activeTab==='tiktok' ) {{ '_social' }} @endif
        data-vue-placeholder>
        <div class="creatives-list__items">
            @for ($i = 0; $i < $perPage; $i++) <div style="padding: 0;" class="creative-item">
                <div class="creative-item__placeholder creative-item__placeholder--{{ $activeTab }}">
                    <img src="/img/empty.svg" alt="placeholder">
                </div>
        </div>
        @endfor

        {{-- Компонент деталей креатива (Vue Island) --}}
        <x-creatives.vue.creative-details :showSimilarCreatives="$showSimilarCreatives"
            :translations="$detailsTranslations ?? []" />
    </div>
</div>
</div>




{{--
Компонент для списка креативов с использованием Vue Islands.
Передайте необходимые параметры через data-vue-props.
Пример использования:
<x-creatives.vue.list :listTranslations="$listTranslations" :perPage="12" />
--}}