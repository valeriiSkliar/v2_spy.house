@extends('layouts.main')

@section('page-content')

<x-landings.sort-selects />


<x-landings.form />

<x-landings.table :landings="$landings" />


    {{ $landings->links() }}


@endsection

@vite('resources/js/pages/landings.js')