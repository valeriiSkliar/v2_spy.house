@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
<div data-blog-ajax-url="{{ route('api.blog.list') }}" id="blog-articles-container" class="blog-list">



    {{-- AJAX container for articles --}}
    @if($articles->count() > 0)
    <x-blog.list.articles-list :articles="$articles->skip(1)" :heroArticle="$articles->first()" />
    @else
    <x-blog.blog-no-results-found :query="$query" />
    @endif

    <div class="full-width">
        <a href="#" target="_blank" class="banner-item">
            <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
        </a>
    </div>

</div>

{{-- AJAX container for pagination --}}
<div id="blog-pagination-container" data-pagination-container>
    @if($articles->hasPages())
    {{ $articles->links() }}
    @endif
</div>


@endsection

@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection

@push('scripts')
@vite(['resources/js/pages/blogs.js'])
@endpush