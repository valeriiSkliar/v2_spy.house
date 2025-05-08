@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">Change Email</h1>
    <div class="section">
        <x-profile.change-email-form 
            :confirmation-method="$user->authenticator_enabled ? 'authenticator' : 'email'"
            :user="$user"
        />
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection 