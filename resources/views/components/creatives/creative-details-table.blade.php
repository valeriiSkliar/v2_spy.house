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
        <div class="details-table__col">Advertising networks</div>
        <div class="details-table__col"><a href="#" class="link _gray">{{ $network ?? 'Pushhouse' }}</a></div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">Country</div>
        <div class="details-table__col"><img src="{{ $flagIcon ?? '/img/flags/UA.svg' }}" alt="">{{ $country ??
            'Bangladesh' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">Language</div>
        <div class="details-table__col">{{ $language ?? 'English' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">First display date</div>
        <div class="details-table__col">{{ $firstDate ?? 'Mar 02, 2025' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">Last display date</div>
        <div class="details-table__col">{{ $lastDate ?? 'Mar 02, 2025' }}</div>
    </div>
    <div class="details-table__row">
        <div class="details-table__col">Status</div>
        <div class="details-table__col">
            <div class="creative-status icon-dot">{{ $status ?? 'Active' }}</div>
        </div>
    </div>
    @endif
</div>