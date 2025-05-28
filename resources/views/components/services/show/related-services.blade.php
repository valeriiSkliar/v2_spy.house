@props(['relatedServices' => [], 'title' => __('services.related_services_title')])

<h2>{{ $title }}</h2>
<div class="market-list">
    <div class="row _offset15">
        @foreach($relatedServices as $relatedService)
        <x-services.index.list.service-card :service="$relatedService" :route="'services.show'" :target="'_blank'"
            :buttonText="__('services.buttons.more_info')" />
        @endforeach
    </div>
</div>