@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
<div x-data="blogPageSimple(serverData)" x-blog-loading="loading">
    <x-blog.search-block />

    {{-- Основной контейнер для статей --}}
    <div data-blog-ajax-url="{{ route('api.blog.list') }}" id="blog-articles-container" class="blog-list"
        :class="{ 'blog-list__no-results': showNoResults }">
        {{-- Server-side rendered content --}}
        @if(($articles && $articles->count() > 0) || $heroArticle)
        <x-blog.list.articles-list :articles="$articles" :heroArticle="$heroArticle" />
        @else
        <x-blog.blog-no-results-found :query="$query" />
        @endif
    </div>

    {{-- Контейнер пагинации --}}
    <div class="pagination-wrapper">
        {{-- Static Pagination (показывается до инициализации Alpine.js) --}}
        <div id="blog-pagination-container" data-pagination-container x-show="!initialized && hasPagination"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            @if($articles && $articles->hasPages())
            {{ $articles->links() }}
            @endif
        </div>

        {{-- Dynamic Pagination (показывается после инициализации Alpine.js) --}}
        <div x-show="initialized && hasPagination" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <x-blog.pagination.dynamic-pagination />
        </div>
    </div>


    @section('bottom-banner')
    <a href="#" target="_blank" class="banner-item mb-20">
        <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
    </a>
    @endsection


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
    articles: @json($articles ? $articles->items() : []),
    heroArticle: @json($heroArticle),
    totalCount: {{ $totalCount }},
    
    // Пагинация
    currentPage: {{ $currentPage }},
    totalPages: {{ $totalPages }},
    hasPagination: {{ ($articles && $articles->hasPages()) ? 'true' : 'false' }},
    
    // Фильтры
    filters: {
        search: @json($filters['search'] ?? ''),
        category: @json($filters['category'] ?? ''),
        sort: @json($filters['sort'] ?? 'latest'),
        direction: @json($filters['direction'] ?? 'desc')
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