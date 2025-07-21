@extends('layouts.main-app')

@section('page-content')
<span style="display: none;" id="landings-page-content"></span>
<x-landings.sort-selects :sortOptions="$sortOptions" :perPageOptions="$perPageOptions" :selectedSort="$selectedSort"
    :selectedPerPage="$selectedPerPage" :filters="$filters" :sortOptionsPlaceholder="$sortOptionsPlaceholder"
    :perPageOptionsPlaceholder="$perPageOptionsPlaceholder" />

<x-landings.form />
<div id="landings-content-wrapper">
    @include('pages.landings._content_wrapper', [
    'landings' => $landings,
    'sortOptions' => $sortOptions,
    'paginationOptions' => $paginationOptions,
    'currentSort' => $currentSort,
    'currentPerPage' => $currentPerPage,
    'viewConfig' => $viewConfig,
    ])
</div>

@endsection

@push('scripts')
<script>
    window.routes = {
    landingsAjaxList: '{{ route("landings.list.ajax") }}',
    landingsAjaxStore: '{{ route("landings.store.ajax") }}',
    landingsAjaxDestroyBase: '{{ route("landings.destroy.ajax", ["landing" => ":id"]) }}',
};
</script>
@vite(['resources/js/landings.js'])
@vite('resources/js/landings.js')
@endpush