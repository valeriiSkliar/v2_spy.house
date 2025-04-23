@props(['type' => 'success', 'dismissible' => true])

@php
$classes = match($type) {
'success' => 'alert-success',
'danger' => 'alert-danger',
'warning' => 'alert-warning',
'info' => 'alert-info',
default => 'alert-primary'
};
@endphp

<div class="alert {{ $classes }} {{ $dismissible ? 'alert-dismissible' : '' }} fade show" role="alert">
    {{ $slot }}

    @if($dismissible)
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    @endif
</div>