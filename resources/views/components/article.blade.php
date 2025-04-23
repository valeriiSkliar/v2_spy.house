@props(['big' => false, 'fullWidth' => false])

<div class="article {{ $big ? '_big' : '' }} {{ $fullWidth ? 'full-width' : '' }}">
    {{ $thumb ?? '' }}

    @if($big)
    <div class="article__row">
        <div class="article__cat d-none d-md-block">
            {{ $category ?? '' }}
        </div>
        <div class="article__info">
            {{ $info ?? '' }}
        </div>
    </div>
    @else
    <div class="article__info">
        {{ $info ?? '' }}
    </div>
    @endif

    {{ $title ?? '' }}

    @if($big)
    <div class="article__desc">{{ $description ?? '' }}</div>
    <div class="article__cat d-md-none">
        {{ $category ?? '' }}
    </div>
    @else
    <div class="article__cat">
        {{ $category ?? '' }}
    </div>
    @endif
</div>