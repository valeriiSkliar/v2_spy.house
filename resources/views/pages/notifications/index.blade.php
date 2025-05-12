@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>{{ __('notifications.title') }}</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-common.base-select
            id="per-page-select"
            :selected="['label' => __('notifications.per_page', ['count' => request('per_page', 12)]), 'value' => request('per_page', 12), 'order' => 1]"
            :options="[
                ['label' => '12', 'value' => '12', 'order' => 1],
                ['label' => '24', 'value' => '24', 'order' => 2],
                ['label' => '48', 'value' => '48', 'order' => 3],
                ['label' => '96', 'value' => '96', 'order' => 4]
            ]"
            :placeholder="__('notifications.per_page', ['count' => 12])"
            icon="list" />
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
            @csrf
            <button type="submit" class="btn _flex _green _medium w-100">{{ __('notifications.mark_all_read') }}</button>
        </form>
    </div>
</div>

<div class="notification-list">
    @foreach($notifications['items'] as $notification)
    <x-notification-item
        :id="$notification['id']"
        :read="$notification['read']"
        :date="$notification['date']"
        :title="$notification['title']"
        :content="$notification['content']"
        :hasButton="$notification['hasButton'] ?? false" />
    @endforeach
</div>

@if($notifications['pagination']->hasPages())
    <div class="pagination-container">
        {{ $notifications['pagination']->links() }}
    </div>
@endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const perPageSelect = document.getElementById('per-page-select');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function(e) {
                const perPage = e.target.value;
                window.location.href = `{{ route('notifications.index') }}?per_page=${perPage}`;
            });
        }
    });
</script>
@endsection