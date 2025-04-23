@extends('layouts.app')

@section('content')

<body class="text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <div class="navigation-bg"></div>
    <div class="wrapper">
        @include('partials.header')
        <main class="main">
            <div class="content">
                @yield('page-content')
            </div>
            @include('partials.sidebar')
        </main>
    </div>
    @include('partials.footer')
    @include('partials.modals')
</body>
@endsection