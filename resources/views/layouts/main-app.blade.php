@extends('layouts.body')

@section('content')
<div class="navigation-bg"></div>
@yield('search-bg')
<div
    class="wrapper {{ Route::is('blog.index') || Route::is('blog.category') || Route::is('blog.show') ? '_page' : '' }}">
    {{-- header --}}
    @include('partials.header')
    {{-- main content --}}
    <main class="main">
        <div
            class="{{ Route::is('blog.index') || Route::is('blog.category') || Route::is('blog.show') ? 'container' : 'content' }}">
            @yield('page-content')
        </div>
        {{-- sidebar --}}
        @include('partials.sidebar')
    </main>

    {{-- fullscreen loader --}}
    <x-common.fullscreen-loader :active="false" />
</div>

{{-- footer --}}
{{-- @include('partials.footer') --}}
@endsection