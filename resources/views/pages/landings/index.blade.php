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

<x-landings.table :landings="$landings" />


    {{ $landings->links() }}


@endsection