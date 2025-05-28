@extends('layouts.authorized')

@section('page-content')
<x-services.show.back-to-list-button :route="'services.index'" :text="__('services.buttons.back_to_list')" />
<x-services.show.details-block :service="$service" :isPromo="$service['code']" :userRating="$service['userRating']" />

<x-services.show.related-services :relatedServices="$relatedServices" :title="__('services.related_services_title')" />
@endsection

@push('scripts')
@vite(['resources/js/services.js'])
@endpush