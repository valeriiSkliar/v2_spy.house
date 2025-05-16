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