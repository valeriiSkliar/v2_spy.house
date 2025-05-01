@extends('layouts.app')

@section('content')

<div class="wrapper">
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