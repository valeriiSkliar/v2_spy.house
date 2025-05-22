@extends('layouts.authorized')

@section('page-content')
<x-services.show.back-to-list-button :route="'services.index'" :text="'To the list of services'" />
<x-services.show.details-block :service="$service" :isPromo="$service['code']" :userRating="$service['userRating']" />

<x-services.show.related-services :relatedServices="$relatedServices" :title="'Offers from other companies'" />
@endsection

@push('scripts')
@vite(['resources/js/services.js'])
@endpush