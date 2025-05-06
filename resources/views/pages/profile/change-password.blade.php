@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">Change password</h1>
    <div class="section">
        <x-profile.change-password-form />
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection