@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
<div x-data="blogPage" x-init="initFromServer(serverData)" x-blog-loading="loading">
    <x-blog.search-block />

    <div data-blog-ajax-url="{{ route('api.blog.list') }}" id="blog-articles-container" class="blog-list"
        :class="{ 'blog-list__no-results': showNoResults }">
        {{-- Server-side rendered content --}}
        @if($articles->count() > 0)
        <x-blog.list.articles-list :articles="$articles->skip(1)" :heroArticle="$articles->first()" />
        @else
        <x-blog.blog-no-results-found :query="$query" />
        @endif
    </div>

    {{-- Pagination --}}
    <div id="blog-pagination-container" data-pagination-container x-show="showPagination">
        @if($articles->hasPages())
        {{ $articles->links() }}
        @endif
    </div>
</div>

{{-- Данные сервера для Alpine.js --}}
<script>
    // Серверные данные для инициализации Alpine.js компонента
window.serverData = {
    // Статьи
    articles: @json($articles->skip(1)->values()),
    heroArticle: @json($articles->first()),
    totalCount: {{ $totalCount }},
    
    // Пагинация
    currentPage: {{ $currentPage }},
    totalPages: {{ $totalPages }},
    hasPagination: {{ $articles->hasPages() ? 'true' : 'false' }},
    
    // Фильтры
    filters: {
        search: @json($filters['search'] ?? ''),
        category: @json($filters['category'] ?? ''),
        sort: @json($filters['sort'] ?? 'latest'),
        direction: 'desc'
    },
    
    // Данные для сайдбара
    categories: @json($categories['categories']),
    popularPosts: @json($categories['popularPosts']),
    
    // AJAX URL
    ajaxUrl: @json(route('api.blog.list'))
};
</script>

{{-- <div class="full-width">
    <a href="#" target="_blank" class="banner-item">
        <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
    </a>
</div> --}}

{{-- AJAX container for pagination --}}
{{-- <div id="blog-pagination-container" data-pagination-container>
    @if($articles->hasPages())
    {{ $articles->links() }}
    @endif
</div> --}}


@endsection

{{-- @section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection --}}