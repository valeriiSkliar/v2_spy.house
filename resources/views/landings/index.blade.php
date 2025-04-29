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

<x-landings-table :landings="$landings" />

<x-pagination :currentPage="$landings->currentPage()" :totalPages="$landings->lastPage()" />

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLandingModal" tabindex="-1" aria-labelledby="deleteLandingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLandingModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this landing page record? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form id="deleteModalForm" method="POST" action=""> {{-- Action will be set by JS --}}
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@vite('resources/js/pages/landings.js')