@extends('layouts.main')

@section('page-content')

<x-landings.sort-selects 
    :sortOptions="$sortOptions" 
    :perPageOptions="$perPageOptions" 
    :selectedSort="$selectedSort" 
    :selectedPerPage="$selectedPerPage" 
    :filters="$filters" 
/>


<x-landings.form />

<x-landings.table :landings="$landings" />


    {{ $landings->links() }}


@endsection

{{-- @vite('resources/js/pages/landings.js') --}}