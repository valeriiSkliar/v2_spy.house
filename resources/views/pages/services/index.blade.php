@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <x-services.index.header.page-h1 title="{{ __('services.title') }}" />
    <x-services.index.header.sort-selects :filters="$filters" :sortOptions="$sortOptions"
        :perPageOptions="$perPageOptions" :sortOptionsPlaceholder="$sortOptionsPlaceholder"
        :perPageOptionsPlaceholder="$perPageOptionsPlaceholder" :selectedSort="$selectedSort"
        :selectedPerPage="$selectedPerPage" />
</div>
<x-services.index.filters.filter-section :categoriesOptions="$categoriesOptions" :bonusesOptions="$bonusesOptions"
    :filters="$filters" :sortOptionsPlaceholder="$sortOptionsPlaceholder"
    :perPageOptionsPlaceholder="$perPageOptionsPlaceholder" :selectedCategory="$selectedCategory"
    :selectedBonuses="$selectedBonuses" :categoriesOptionsPlaceholder="$categoriesOptionsPlaceholder"
    :bonusesOptionsPlaceholder="$bonusesOptionsPlaceholder" />

<div id="services-container" data-services-ajax-url="{{ route('api.services.list') }}">
    @if ($services->isEmpty())
    <x-services.index.list.empty-services />
    @else
    <x-services.index.list.services-list :services="$services" />
    @endif
</div>

<div id="services-pagination-container" data-pagination-container>
    @if ($services->hasPages())
    <x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
    @else
    <x-services.index.list.empty-services />
    @endif
</div>
@endsection

@push('scripts')
@vite(['resources/js/services.js'])
@endpush