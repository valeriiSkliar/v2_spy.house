@extends('layouts.main-app')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>{{ __('notifications.title') }}</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <x-common.base-select :id="'per-page'" :selected="$selectedPerPage" :options="$perPageOptions"
                :placeholder="$perPageOptionsPlaceholder" :icon="'list'" />
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <button id="mark-all-read" @disabled($unreadCount===0) type="button" class="btn _flex _green _medium w-100"
            data-url="{{ route('notifications.markAllAsRead') }}">{{ __('notifications.mark_all_read') }}</button>
    </div>
</div>

{{-- AJAX Container for notifications content --}}
<div id="notifications-container" data-notifications-ajax-url="{{ route('api.notifications.list') }}">
    @if (count($notifications['items']) === 0)
    <x-notifications.empty-notifications />
    @else
    <x-notifications.notifications-list :notifications="$notifications['items']" />
    @endif
</div>

{{-- AJAX Container for pagination --}}
<div id="notifications-pagination-container" data-pagination-container>
    @if($notifications['pagination']->hasPages())
    <div class="pagination-container">
        {{ $notifications['pagination']->links() }}
    </div>
    @endif
</div>
@endsection