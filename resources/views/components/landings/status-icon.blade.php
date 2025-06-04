@props(['status'])

@php
$iconClass = match($status) {
'pending' => 'icon-reload remore_margin spinning',
'in_progress' => 'icon-reload remore_margin spinning',
'completed' => 'icon-check remore_margin',
'failed' => 'icon-warning remore_margin',
default => 'icon-help remore_margin'
};
@endphp

<span class="btn-icon {{ $iconClass }}"></span>