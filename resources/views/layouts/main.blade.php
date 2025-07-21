@extends('layouts.app')

@section('content')

<div class="wrapper">
    <x-common.fullscreen-loader :active="false" />

    @include('partials.header')
    <main class="main">
        <div class="content">
            @yield('page-content')
        </div>
        @include('partials.sidebar')
    </main>
</div>
@include('partials.modals')
@endsection
@include('partials.footer')