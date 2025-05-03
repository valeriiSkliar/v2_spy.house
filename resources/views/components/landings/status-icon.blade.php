@props(['status'])

@php
$iconClass = match($status) {
    'pending' => 'icon-reload',
    'completed' => 'icon-check',
    'failed' => 'icon-warning',
    default => 'icon-help'
};
@endphp

<span class="btn-icon {{ $iconClass }}"></span>