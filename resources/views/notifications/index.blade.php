@extends('layouts.authorized')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Notifications</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-base-select-icon
            :selected="'On page — 12'"
            :options="['12', '24', '48', '96']"
            icon="list" />
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
            @csrf
            <button type="submit" class="btn _flex _green _medium w-100">Read all</button>
        </form>
    </div>
</div>

<div class="notification-list">
    @foreach($notifications as $notification)
    <x-notification-item
        :id="$notification['id']"
        :read="$notification['read']"
        :date="$notification['date']"
        :title="$notification['title']"
        :content="$notification['content']"
        :hasButton="$notification['hasButton'] ?? false" />
    @endforeach
</div>

<x-pagination :currentPage="1" :totalPages="3" />
@endsection