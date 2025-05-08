@props(['title' => '', 'description' => '', 'class' => ''])

<div class="message mb-20 pt-md-4 mt-md-3 {{ $class }}">
    <span class="icon-i"></span>
    <div class="message__txt"><span class="font-weight-500">{{ $title }}</span> {{ $description }}</div>
</div>