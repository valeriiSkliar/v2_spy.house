@extends('layouts.body')

@section('content')
<div class="wrapper _page pt-0">
    @include('pages.homePage.header')
    @include('pages.homePage.offer')
    @include('pages.homePage.features')
    @include('pages.homePage.creatives')
    @include('pages.homePage.prices')
    @include('pages.homePage.reviews')
    @include('pages.homePage.download-creatives')
    @include('pages.homePage.blogs')
    @include('pages.homePage.footer')
    @include('pages.homePage.aside')
</div>
@include('partials.modals')
@endsection