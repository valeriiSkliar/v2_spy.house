@extends('layouts.main')

@section('content')

<div class="search-bg"></div>
@include('partials.search-form-blog')
<div class="wrapper _page">
    @include('partials.header-blog')
    <main class="main">
        <div class="container">
            @yield('breadcrumbs')
            <div class="blog-layout">
                <div class="blog-layout__content">
                    @include('partials.blog-mobile-filter')
                    @yield('page-content')
                </div>
                <aside class="blog-layout__aside">
                    @include('partials.blog-sidebar')
                </aside>
            </div>
            @yield('bottom-banner')
        </div>
        @include('partials.sidebar-blog')
    </main>
</div>
@endsection