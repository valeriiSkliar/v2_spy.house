@props([
'listTranslations' => [],
'perPage' => 12,
'activeTab' => 'push',
])
<div class="vue-component-wrapper" data-vue-component="CreativesListComponent" data-vue-props='{
        "translations": {{ json_encode($listTranslations) }},
        "perPage": {{ $perPage }},
        "activeTab": {{ json_encode($activeTab) }}
    }' data-vue-placeholder-manual>

    <!-- Placeholder карточек креативов (количество = perPage) -->
    <div class="creatives-list" data-vue-placeholder>
        <div class="creatives-list__items">
            @for ($i = 0; $i < $perPage; $i++) <div style="padding: 0;" class="creative-item">
                <div class="creative-item__placeholder creative-item__placeholder--{{ $activeTab }}">
                    <img src="/img/empty.svg" alt="placeholder">
                </div>
        </div>
        @endfor
    </div>
</div>
</div>




{{--
Компонент для списка креативов с использованием Vue Islands.
Передайте необходимые параметры через data-vue-props.
Пример использования:
<x-creatives.vue.list :listTranslations="$listTranslations" :perPage="12" />
--}}