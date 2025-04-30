@extends('layouts.authorized')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Services</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <div class="base-select">
                <div class="base-select__trigger"><span class="base-select__value">Sort by — Transitions High to Low</span><span class="base-select__arrow"></span></div>
                <ul class="base-select__dropdown" style="display: none;">
                    <li class="base-select__option">Transitions High to Low</li>
                    <li class="base-select__option">Transitions Low to High</li>
                    <li class="base-select__option">Rating High to Low</li>
                    <li class="base-select__option">Rating Low to High</li>
                    <li class="base-select__option">Views High to Low</li>
                    <li class="base-select__option">Views Low to High</li>
                    <li class="base-select__option is-selected">Default</li>
                </ul>
            </div>
            <span class="icon-sort"></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <div class="base-select">
                <div class="base-select__trigger"><span class="base-select__value">On page — 12</span><span class="base-select__arrow"></span></div>
                <ul class="base-select__dropdown" style="display: none;">
                    <li class="base-select__option is-selected">12</li>
                    <li class="base-select__option">24</li>
                    <li class="base-select__option">48</li>
                    <li class="base-select__option">96</li>
                </ul>
            </div>
            <span class="icon-list"></span>
        </div>
    </div>
</div>
<div class="filter">
    <div class="filter__trigger-mobile">
        <span class="btn-icon _dark _big _filter">
            <span class="icon-filter"></span>
            <span class="icon-up font-24"></span>
        </span>
        Filter
    </div>
    <div class="filter__content">
        <div class="row align-items-end">
            <div class="col-12 col-md-auto flex-grow-1">
                <div class="row">
                    <div class="col-12 col-lg-4 mb-10">
                        <form action="{{ route('services.index') }}" method="GET" id="searchForm">
                            <div class="form-search">
                                <span class="icon-search"></span>
                                <input type="search" name="search" placeholder="Search by Keyword" value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 mb-10">
                        <div class="base-select">
                            <div class="base-select__trigger"><span class="base-select__value">All Categories</span><span class="base-select__arrow"></span></div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected" data-value="">All Categories</li>
                                @foreach($categories as $category)
                                <li class="base-select__option" data-value="{{ $category['id'] }}">{{ $category['name'] }}</li>
                                @endforeach
                            </ul>
                            <input type="hidden" name="category" form="searchForm" value="{{ request('category') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 mb-10">
                        <div class="base-select">
                            <div class="base-select__trigger"><span class="base-select__value">All bonuses</span><span class="base-select__arrow"></span></div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected">All bonuses</li>
                                <li class="base-select__option">With discount</li>
                                <li class="base-select__option">Without discount</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto mb-10">
                <div class="reset-btn">
                    <a href="{{ route('services.index') }}" class="btn-icon"><span class="icon-clear"></span> <span class="ml-2 d-md-none">Reset</span></a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="market-list">
    <div class="row _offset15">
        @foreach($services as $service)
        <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
            <div class="market-list__item">
                <div class="market-list__thumb"><a href="{{ route('services.show', $service['id']) }}"><img src="{{ $service['logo'] }}" alt="{{ $service['name'] }}"></a></div>
                <h4><a href="{{ route('services.show', $service['id']) }}">{{ $service['name'] }}</a></h4>
                <div class="market-list__desc"><a href="{{ route('services.show', $service['id']) }}">{{ $service['description'] }}</a></div>
                <ul class="market-list__info">
                    <li class="icon-view">{{ number_format($service['views']) }}</li>
                    <li class="icon-link">{{ number_format($service['transitions']) }}</li>
                    <li class="icon-star">{{ number_format($service['rating'], 1) }}</li>
                </ul>
                <div class="market-list__btn">
                    <a href="{{ route('services.redirect', $service['id']) }}" class="btn _flex _border-green w-100" target="_blank">
                        <span>Visit Site <span class="icon-more"></span></span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
<x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category filter
        const categoryOptions = document.querySelectorAll('.base-select__option[data-value]');
        const categoryInput = document.querySelector('input[name="category"]');
        const categoryValue = document.querySelector('.base-select__value');
        const searchForm = document.getElementById('searchForm');

        categoryOptions.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                categoryInput.value = value;
                searchForm.submit();
            });
        });

        // Set selected category in dropdown
        const selectedCategory = "{{ request('category') }}";
        if (selectedCategory) {
            const option = document.querySelector(`.base-select__option[data-value="${selectedCategory}"]`);
            if (option) {
                categoryValue.textContent = option.textContent;
            }
        }

        // Search form submit on enter
        const searchInput = document.querySelector('input[name="search"]');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchForm.submit();
            }
        });
    });
</script>
@endsection