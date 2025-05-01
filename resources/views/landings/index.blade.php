@extends('layouts.authorized')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>{{ __('landings.index.title') }}</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-base-select-icon
            :selected="__('landings.index.sort.placeholder', ['default' => __('landings.index.sort.options.newest')])"
            :options="__('landings.index.sort.options')"
            icon="sort" />
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-base-select-icon
            :selected="__('landings.index.pagination.placeholder', ['default' => __('landings.index.pagination.options.12')])"
            :options="__('landings.index.pagination.options')"
            icon="list" />
    </div>
</div>

<x-landing-form />

<x-landings-table :landings="$landings" />

<x-pagination :currentPage="$landings->currentPage()" :totalPages="$landings->lastPage()" />


@endsection

@vite('resources/js/pages/landings.js')