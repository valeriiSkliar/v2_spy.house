@extends('layouts.body')

@section('content')
<div class="navigation-bg"></div>
@yield('search-bg')
<div class="wrapper {{ Request::is('/blog') ? '' : '_page' }}">
    {{-- header --}}
    @include('partials.header')
    {{-- main content --}}
    <main class="main">
        <div class="{{ Request::is('/blog') ? 'content' : 'container' }}">
            @yield('page-content')
        </div>
    </main>
    {{-- fullscreen loader --}}
    <x-common.fullscreen-loader :active="false" />
</div>

{{-- footer --}}
{{-- @include('partials.footer') --}}
@endsection