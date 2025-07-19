@extends('layouts.main-app')

@section('search-bg')
<div class="search-bg"></div>
@include('partials.blog.search-form')
@endsection

@section('page-content')
@yield('blog-content')
@endsection

@section('bottom-banner')

@endsection

@push('scripts')
@vite(['resources/js/pages/blogs.js'])
@endpush