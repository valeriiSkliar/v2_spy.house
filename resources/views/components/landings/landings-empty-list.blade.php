@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Landings</h1>
    </div>
    {{-- <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-common.base-select
            :selected="'Sort By — Newest First'"
            :options="['Newest First', 'Oldest First', 'Status (A-Z)', 'Status (Z-A)', 'URL (A-Z)', 'URL (Z-A)']"
            icon="sort"
            :disabled="true" />
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-common.base-select
            :selected="'On page — 12'"
            :options="['12', '24', '48', '96']"
            icon="list"
            :disabled="true" />
    </div> --}}
</div>

<x-landings.form  />

<x-empty-landings />
@endsection