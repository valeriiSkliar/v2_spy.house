@props([
'vueTranslations' => [],
'apiEndpoint' => '/api/creatives',
'viewMode' => 'list'
])
<div class="vue-component-wrapper" data-vue-component="CreativesListComponent" data-vue-props='{
        "translations": {{ json_encode($vueTranslations) }},
        "apiEndpoint": {{ json_encode($apiEndpoint) }},
        "viewMode": {{ json_encode($viewMode) }},
        "enableInfiniteScroll": false,
        "enableSelection": true
    }'>

</div>

{{--
Компонент для списка креативов с использованием Vue Islands.
Передайте необходимые параметры через data-vue-props.
Пример использования:
<x-creatives.vue.list :vueTranslations="$vueTranslations" />