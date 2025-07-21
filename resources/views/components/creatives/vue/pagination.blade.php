@props([
'translations' => [],
'showInfo' => true,
'maxVisiblePages' => 7,
'alwaysShowFirstLast' => true,
])

<div class="vue-component-wrapper" data-vue-component="PaginationComponent" data-vue-props='{
        "translations": {{ json_encode($translations) }},
        "showInfo": {{ json_encode($showInfo) }},
        "maxVisiblePages": {{ $maxVisiblePages }},
        "alwaysShowFirstLast": {{ json_encode($alwaysShowFirstLast) }}
    }' data-vue-placeholder-manual>

    <!-- Placeholder for pagination -->
    <div class="pagination-placeholder" data-vue-placeholder>
        <nav class="pagination-nav" role="navigation" aria-label="pagination">
            <ul class="pagination-list">
                <li><a class="pagination-link prev disabled" aria-disabled="true" href="#"><span
                            class="icon-prev"></span> <span class="pagination-link__txt">Предыдущая</span></a></li>
                <li><a class="pagination-link active" href="#" aria-current="page">1</a></li>
                <li><a class="pagination-link" href="#">2</a></li>
                <li><a class="pagination-link" href="#">3</a></li>
                <li><span class="pagination-dots">...</span></li>
                <li><a class="pagination-link" href="#">10</a></li>
                <li><a class="pagination-link next" aria-disabled="false" href="#"><span
                            class="pagination-link__txt">Следующая</span> <span class="icon-next"></span></a></li>
            </ul>
            @if($showInfo)
            <div class="pagination-info">
                Страница 1 из 10 (1-12 из 120)
            </div>
            @endif
        </nav>
    </div>
</div>

{{--
Компонент пагинации для креативов с использованием Vue Islands.
Передайте необходимые параметры через props.

Пример использования:
<x-creatives.vue.pagination :translations="$paginationTranslations" :showInfo="true" :maxVisiblePages="7"
    :alwaysShowFirstLast="true" />
--}}