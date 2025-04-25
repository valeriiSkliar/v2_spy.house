@extends('layouts.main')

@section('content')

<!-- <body class="text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <div class="navigation-bg"></div>
    <div class="search-bg"></div> -->


<div class="wrapper _page">
    @include('partials.header-blog')
    <!-- @include('partials.search-form') -->
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