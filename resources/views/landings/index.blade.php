@extends('layouts.authorized')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Landings</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-base-select-icon
            :selected="'Sort By — Newest First'"
            :options="['Newest First', 'Oldest First', 'Status (A-Z)', 'Status (Z-A)', 'URL (A-Z)', 'URL (Z-A)']"
            icon="sort" />
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <x-base-select-icon
            :selected="'On page — 12'"
            :options="['12', '24', '48', '96']"
            icon="list" />
    </div>
</div>

<x-landing-form />

<x-landings-table :landings="[
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye/v-socsetyah-rav-socsetyah-rav-socsetyah-ra', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
        ['id' => '84513', 'url' => 'https://novosti365.info/socialnye', 'date' => '2024-12-29 / 2024-12-29', 'source' => 'Mixadvert'],
    ]" />

<x-pagination :currentPage="1" :totalPages="3" />
@endsection