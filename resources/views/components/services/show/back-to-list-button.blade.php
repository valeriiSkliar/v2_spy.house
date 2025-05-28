@props(['route' => 'services.index', 'text' => __('services.buttons.back_to_list')])

<div class="mb-20">
    <a href="{{ route($route) }}" class="btn _flex _medium _gray"><span class="icon-prev mr-2 font-18"></span> {{ $text
        }}</a>
</div>