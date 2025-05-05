<div class="market-list">
    <div class="row _offset15">
        @foreach($services as $service)
        <x-services.service-card :service="$service" />
        @endforeach
    </div>
</div>