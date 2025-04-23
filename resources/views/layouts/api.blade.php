@extends('layouts.authorized')

@section('page-content')
<h1 class="mb-25">API</h1>

@php
$navItems = [
[
'title' => 'Flows',
'items' => [
['name' => 'Retrieving a list of flows', 'url' => '#flows', 'active' => false],
['name' => 'Creating a flow', 'url' => '#flow-create', 'active' => true],
['name' => 'Updating a flow', 'url' => '#flow-update', 'active' => false],
['name' => 'Retrieving flow details', 'url' => '#flow-details', 'active' => false],
['name' => 'Deleting a flow', 'url' => '#flow-delete', 'active' => false],
['name' => 'Restoring a flow', 'url' => '#flow-restore', 'active' => false],
['name' => 'Activating a flow', 'url' => '#flow-activate', 'active' => false],
['name' => 'Pausing a flow', 'url' => '#flow-pause', 'active' => false],
['name' => 'Downloading an integration file', 'url' => '#flow-download', 'active' => false],
['name' => 'Creating a flow link', 'url' => '#flow-link', 'active' => false],
],
],
[
'title' => 'Reports',
'items' => [
['name' => 'Retrieving statistics', 'url' => '#statistics', 'active' => false],
['name' => 'Retrieving click data', 'url' => '#clicks', 'active' => false],
],
],
[
'title' => 'Account',
'items' => [
['name' => 'Transferring funds', 'url' => '#account-transfer', 'active' => false],
['name' => 'Retrieving account balance', 'url' => '#account-balance', 'active' => false],
],
],
];
@endphp

<x-api-mobile-filter :navItems="$navItems" />

<div class="main-api">
    <div class="main-api__content">
        @yield('api-content')
    </div>
    <div class="main-api__aside">
        <x-api-nav :navItems="$navItems" />
    </div>
</div>
@endsection