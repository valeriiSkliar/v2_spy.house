<div class="details-table">
    @if(isset($items))
    @foreach($items as $item)
    <div class="details-table__row">
        <div class="details-table__col">{{ $item['label'] }}</div>
        <div class="details-table__col">
            @if(isset($item['flag']))
            <img src="{{ $item['flag'] }}" alt="">
            @endif
            @if(isset($item['link']))
            <a href="{{ $item['link'] }}" class="link _gray">{{ $item['value'] }}</a>
            @else
            @if(isset($item['status']))
            <div class="creative-status icon-dot">{{ $item['value'] }}</div>
            @else
            {{ $item['value'] }}
            @endif
            @endif
        </div>
    </div>
    @endforeach
    @else
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.network') }}</div>
        <div class="details-table__col"><a href="#" class="link _gray">{{ $network ?? 'Pushhouse' }}</a></div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.country') }}</div>
        <div class="details-table__col"><img src="{{ $flagIcon ?? '/img/flags/UA.svg' }}" alt="">{{ $country ??
            'Bangladesh' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.language') }}</div>
        <div class="details-table__col">{{ $language ?? 'English' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.first-display-date') }}</div>
        <div class="details-table__col">{{ Carbon\Carbon::parse($firstDate ?? 'Mar 02, 2025')->format('d M Y') }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.last-display-date') }}</div>
        <div class="details-table__col">{{ Carbon\Carbon::parse($lastDate ?? 'Mar 02, 2025')->format('d M Y') }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">{{ __('creatives.card.status') }}</div>
        <div class="details-table__col">
            <div class="creative-status icon-dot">{{ $status ? __('creatives.details.active') :
                __('creatives.details.inactive') }}</div>
        </div>
    </div>
    @endif
</div>