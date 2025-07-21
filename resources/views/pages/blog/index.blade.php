@extends('layouts.blog-new')


@section('blog-content')
@if($breadcrumbs && count($breadcrumbs) > 0)
<x-breadcrumbs :items="$breadcrumbs" />
@endif
<div class="blog-layout">
    <div class="blog-layout__content">
        @include('partials.blog.mobile-filter')
        <x-blog.search-block />
        <div data-blog-ajax-url="{{ route('api.blog.list') }}" id="blog-articles-container"
            class="blog-list @if($articles->count() == 0) blog-list__no-results @endif">
            {{-- AJAX container for articles --}}
            @if($articles->count() > 0)
            <x-blog.list.articles-list :articles="$articles->skip(1)" :heroArticle="$articles->first()" />
            @else
            <x-blog.blog-no-results-found :query="$query" />
            @endif

        </div>
        <div id="blog-pagination-container" class="pagination-nav" data-pagination-container>
            @if($articles->hasPages())
            {{ $articles->links() }}
            @endif
        </div>
    </div>
    <aside class="blog-layout__aside">
        @include('partials.blog.sidebar')
    </aside>
</div>
@endsection