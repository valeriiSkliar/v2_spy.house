@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>{{ __('notifications.title') }}</h1>
    </div>
</div>

<div class="notification-empty text-center py-5">
    <div class="icon-wrapper mb-4">
        <i class="icon-bell" style="font-size: 48px; color: #ccc;"></i>
    </div>
    <h3>{{ __('notifications.empty.title') }}</h3>
    <p class="text-muted">{{ __('notifications.empty.description') }}</p>
</div>
@endsection 