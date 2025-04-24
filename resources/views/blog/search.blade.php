<!-- resources/views/blog/search.blade.php -->
@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="[
        ['title' => 'Blog', 'url' => route('blog.index')],
        ['title' => 'Search Results']
    ]" />
@endsection

@section('page-content')
<h1 class="mb-25">Search Results for "{{ $query }}"</h1>
<p class="mb-25">Found {{ $totalResults }} {{ Str::plural('result', $totalResults) }}</p>

<div class="blog-list">
    @forelse($articles as $article)
    <x-article>
        <x-slot name="thumb">
            <a href="{{ route('blog.show', $article['slug']) }}" class="article__thumb thumb"><img src="{{ $article['image'] }}" alt="{{ $article['title'] }}"></a>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">{{ $article['date'] }}</div>
                <a href="{{ route('blog.show', $article['slug']) }}#comments" class="article-info__item icon-comment1">{{ count($article['comments']) }}</a>
                <div class="article-info__item icon-view">{{ $article['views'] }}</div>
                <div class="article-info__item icon-rating">{{ $article['rating'] }}</div>
            </div>
        </x-slot>

        <x-slot name="title">
            <a href="{{ route('blog.show', $article['slug']) }}" class="article__title">{{ $article['title'] }}</a>
        </x-slot>

        <x-slot name="category">
            <div class="cat-links">
                <a href="{{ route('blog.category', $article['category']['slug']) }}" data-color="{{ $article['category']['color'] }}">{{ $article['category']['name'] }}</a>
            </div>
        </x-slot>
    </x-article>
    @empty
    <div class="text-center mt-4 mb-4">
        <h2>No results found for "{{ $query }}"</h2>
        <p>Try different keywords or check out our categories below.</p>

        <div class="category-links mt-4">
            @foreach($categories as $category)
            <a href="{{ route('blog.category', $category['slug']) }}" class="btn _flex _medium" data-color="{{ $category['color'] }}">
                {{ $category['name'] }} <span class="ml-2">({{ $category['count'] }})</span>
            </a>
            @endforeach
        </div>
    </div>
    @endforelse
</div>

@if($totalPages > 1)
<x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
@endif
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.category-link').click(function() {
            var color = $(this).data('color');
            $(this).css('color', 'white');
            $(this).css('background-color', color);
            $(this).css('margin', '5px');
        });
    });
</script>
@endsection

@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection