@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
<div x-data="blogPageSimple(serverData)" x-blog-loading="$store.blog.loading">
    <x-blog.search-block />

    {{-- Основной контейнер для статей --}}
    <div data-blog-ajax-url="{{ route('api.blog.list') }}" id="blog-articles-container" class="blog-list"
        :class="{ 'blog-list__no-results': showNoResults }">
        {{-- Server-side rendered content --}}
        @if($articles->count() > 0)
        <x-blog.list.articles-list :articles="$articles->skip(1)" :heroArticle="$articles->first()" />
        @else
        <x-blog.blog-no-results-found :query="$query" />
        @endif
    </div>

    {{-- Контейнер пагинации --}}
    <div class="pagination-wrapper">
        {{-- Static Pagination (показывается до инициализации Alpine.js) --}}
        <div id="blog-pagination-container" data-pagination-container
            x-show="!initialized && $store.blog.pagination.hasPagination"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            @if($articles->hasPages())
            {{ $articles->links() }}
            @endif
        </div>

        {{-- Dynamic Pagination (показывается после инициализации Alpine.js) --}}
        <div x-show="initialized && $store.blog.pagination.hasPagination"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">
            <x-blog.pagination.dynamic-pagination />
        </div>
    </div>

    {{-- <div class="full-width">
        <a href="#" target="_blank" class="banner-item">
            <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
        </a>
    </div> --}}

    @section('bottom-banner')
    <a href="#" target="_blank" class="banner-item mb-20">
        <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
    </a>
    @endsection

    {{-- Loading overlay --}}
    <x-common.fullscreen-loader :active="false" x-bind:class="{ 'active': $store.blog.loading }" />
</div>

{{-- CSS для пагинации --}}
<style>
    .pagination-wrapper {
        margin-top: 2rem;
    }
</style>

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



{{-- AJAX container for pagination --}}
{{-- <div id="blog-pagination-container" data-pagination-container>
    @if($articles->hasPages())
    {{ $articles->links() }}
    @endif
</div> --}}


@endsection