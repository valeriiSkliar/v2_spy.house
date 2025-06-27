@props([
'listTranslations' => [],
'perPage' => 12,
'activeTab' => 'push',
])
<div class="vue-component-wrapper" data-vue-component="CreativesListComponent" data-vue-props='{
        "translations": {{ json_encode($listTranslations) }},
        "perPage": {{ $perPage }},
        "activeTab": {{ json_encode($activeTab) }}
    }'>

    <!-- Placeholder карточек креативов (количество = perPage) -->
    <div class="creatives-list__placeholder" data-vue-placeholder>
        <div class="creatives-list__items">
            @for ($i = 0; $i < $perPage; $i++) <div class="creative-item placeholder">
                <div class="creative-item__header">
                    <div class="creative-item__title placeholder-line"></div>
                    <div class="creative-item__status placeholder-badge"></div>
                </div>
                <div class="creative-item__info">
                    <div class="creative-item__description placeholder-line"></div>
                    <div class="creative-item__meta">
                        <div class="meta-item placeholder-line"></div>
                        <div class="meta-item placeholder-line"></div>
                        <div class="meta-item placeholder-line"></div>
                    </div>
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