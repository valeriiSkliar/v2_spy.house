@props(['type' => 'default', 'icon' => '', 'border' => false])

@php
$classes = match($type) {
'dark' => '_dark',
'gray' => '_gray',
'bg' => '_bg',
'orange' => '_with-border _orange',
'red' => '_with-border _red',
default => ''
};

if($border && $type === 'bg') {
$classes .= ' _with-border';
}
@endphp

<div class="message {{ $classes }}">
    <span class="icon-{{ $icon }}{{ $type === 'orange' || $type === 'red' ? ' font-18' : '' }}"></span>
    <div class="message__txt">{{ $slot }}</div>
</div>