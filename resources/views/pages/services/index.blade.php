@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <x-services.index.header.page-h1 title="Services" />
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
@if ($services->isEmpty())
<x-services.index.list.empty-services />
@else
<x-services.index.list.services-list :services="$services" />
@endif

{{-- {{ $services->links('common.pagination.spy-pagination-default') }} --}}
@if ($services->hasPages())
<x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
@endif
@endsection