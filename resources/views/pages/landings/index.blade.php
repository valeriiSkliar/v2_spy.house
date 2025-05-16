@extends('layouts.main')

@section('page-content')

 <x-landings.sort-selects 
    :sortOptions="$sortOptions" 
    :perPageOptions="$perPageOptions" 
    :selectedSort="$selectedSort" 
    :selectedPerPage="$selectedPerPage" 
    :filters="$filters" 
    :sortOptionsPlaceholder="$sortOptionsPlaceholder"
    :perPageOptionsPlaceholder="$perPageOptionsPlaceholder"
/>



{{--<x-landings.table :landings="$landings" />


    {{ $landings->links() }} --}}
    <x-landings.form />
         {{-- Изначально рендерим контент через Blade partial, как и при AJAX-запросе --}}
    @include('pages.landings._content_wrapper', [
        'landings' => $landings,
        'sortOptions' => $sortOptions,
        'paginationOptions' => $paginationOptions,
        'currentSort' => $currentSort,
        'currentPerPage' => $currentPerPage,
        'viewConfig' => $viewConfig,
    ])


@endsection