@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <x-services.page-h1 title="Services" />
    <x-services.sort-selects :filters="$filters" :sortOptions="$sortOptions" :perPageOptions="$perPageOptions"
        :sortOptionsPlaceholder="$sortOptionsPlaceholder" :perPageOptionsPlaceholder="$perPageOptionsPlaceholder"
        :selectedSort="$selectedSort" :selectedPerPage="$selectedPerPage" />
</div>
<x-services.filter-section :categoriesOptions="$categoriesOptions" :bonusesOptions="$bonusesOptions" :filters="$filters"
    :sortOptionsPlaceholder="$sortOptionsPlaceholder" :perPageOptionsPlaceholder="$perPageOptionsPlaceholder"
    :selectedCategory="$selectedCategory" :selectedBonuses="$selectedBonuses"
    :categoriesOptionsPlaceholder="$categoriesOptionsPlaceholder"
    :bonusesOptionsPlaceholder="$bonusesOptionsPlaceholder" />
<div id="services-container">
    @if ($services->isEmpty())
    <x-services.empty-services />
    @else
    <x-services.services-list :services="$services" />
    @endif

    {{-- {{ $services->links('common.pagination.spy-pagination-default') }} --}}
    @if ($services->hasPages())
    <x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
    @endif
</div>

<!-- Начальные данные для клиентского JavaScript -->
<script type="application/json" id="services-initial-data">
    {!! $initialData !!}
</script>

<!-- Подключаем JavaScript для SPA-функциональности с помощью Vite -->
@vite('resources/js/pages/services/spa/index.js')
@endsection