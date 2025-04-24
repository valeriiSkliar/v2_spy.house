@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="[
        ['title' => 'Blog', 'url' => '#'],
        ['title' => 'Arbitrage', 'url' => '#'],
        ['title' => 'Social networks', 'url' => '#'],
        ['title' => 'How the Arbitration Business Has Changed in 2021 and Forecasts for 2022']
    ]" />
@endsection

@section('page-content')
<div class="blog-list">
    <x-article :big="true" :fullWidth="true">
        <x-slot name="thumb">
            <a href="#" class="article__thumb thumb"><img src="https://blog.spy.house/wp-content/uploads/2023/07/PH_blog_15.png" alt=""></a>
        </x-slot>

        <x-slot name="category">
            <div class="cat-links">
                <a href="#" style="color:#CD4F51;">Push traffic</a>
            </div>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">11.05.25</div>
                <a href="#" class="article-info__item icon-comment1">0</a>
                <div class="article-info__item icon-view">1</div>
                <div class="article-info__item icon-rating">4.5</div>
            </div>
        </x-slot>

        <x-slot name="title">
            <a href="#" class="article__title h1"><span class="article-label">New</span> How the Arbitration Business Has Changed in 2021 and Forecasts for 2022</a>
        </x-slot>

        <x-slot name="description">
            CPA market leaders shared what changes occurred in the arbitrage business in 2021, how they carried out automation in their teams CPA market leaders shared what changes occurred in the arbitrage business in 2021, how they carried out automation in their teams
        </x-slot>
    </x-article>

    @for($i = 0; $i < 2; $i++)
        <x-article>
        <x-slot name="thumb">
            <a href="#" class="article__thumb thumb"><img src="https://blog.spy.house/wp-content/uploads/2023/06/PH_blog_microbidding_02.png" alt=""></a>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">11.05.25</div>
                <a href="#" class="article-info__item icon-comment1">23</a>
                <div class="article-info__item icon-view">12 356</div>
                <div class="article-info__item icon-rating">4.5</div>
            </div>
        </x-slot>

        <x-slot name="title">
            <a href="#" class="article__title">Антикейс: как я потерял $650 в P2E игре в 1 клик</a>
        </x-slot>

        <x-slot name="category">
            <div class="cat-links">
                <a href="#" style="color:#694fcd;">Арбитражнику</a>
                <a href="#" style="color:#33b485;">Полезное</a>
                <a href="#" style="color:#4F98CD;">Кейс </a>
            </div>
        </x-slot>
        </x-article>
        @endfor

        <div class="full-width">
            <a href="#" target="_blank" class="banner-item">
                <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
            </a>
        </div>

        @for($i = 0; $i < 6; $i++)
            <x-article>
            <x-slot name="thumb">
                <a href="#" class="article__thumb thumb"><img src="https://blog.spy.house/wp-content/uploads/2022/04/image8.jpg" alt=""></a>
            </x-slot>

            <x-slot name="info">
                <div class="article-info">
                    <div class="article-info__item icon-date">11.05.25</div>
                    <a href="#" class="article-info__item icon-comment1">23</a>
                    <div class="article-info__item icon-view">12 356</div>
                    <div class="article-info__item icon-rating">4.5</div>
                </div>
            </x-slot>

            <x-slot name="title">
                <a href="#" class="article__title">Антикейс: как я потерял $650 в P2E игре в 1 клик</a>
            </x-slot>

            <x-slot name="category">
                <div class="cat-links">
                    <a href="#" style="color:#694fcd;">Арбитражнику</a>
                    <a href="#" style="color:#33b485;">Полезное</a>
                    <a href="#" style="color:#4F98CD;">Кейс </a>
                </div>
            </x-slot>
            </x-article>
            @endfor
</div>

<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li><a class="pagination-link prev disabled" aria-disabled="true" href=""><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
        <li><a class="pagination-link active" href="#">1</a></li>
        <li><a class="pagination-link" href="#">2</a></li>
        <li><a class="pagination-link" href="#">3</a></li>
        <li><a class="pagination-link next" aria-disabled="false" href="#"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
    </ul>
</nav>
@endsection

@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection