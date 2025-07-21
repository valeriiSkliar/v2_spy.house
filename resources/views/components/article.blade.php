@props(['big' => false, 'fullWidth' => false, 'isNew' => false, 'showMoreButton' => false])

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

    @if(isset($title))
    @if($isNew)
    <div class="article__title {{ $big ? 'h1' : '' }}">
        <span class="article-label">New</span> {{ $title }}
    </div>
    @else
    <div class="article__title {{ $big ? 'h1' : '' }}">{{ $title }}</div>
    @endif
    @endif

    @if($big)
    <div class="article__desc">{{ $description ?? '' }}</div>

    @if($showMoreButton)
    <div class="d-none d-md-block pt-3">
        {{ $moreButton ?? '' }}
    </div>
    @endif

    <div class="article__bottom d-md-none">
        <div class="article__cat">
            {{ $category ?? '' }}
        </div>
        @if($showMoreButton)
        <div class="article__link">
            {{ $moreButton ?? '' }}
        </div>
        @endif
    </div>
    @else
    @if(isset($description) && $description)
    <div class="article__desc">{{ $description }}</div>
    @endif

    <div class="article__bottom">
        <div class="article__cat">
            {{ $category ?? '' }}
        </div>
        @if($showMoreButton)
        <div class="article__link">
            {{ $moreButton ?? '' }}
        </div>
        @endif
    </div>
    @endif
</div>