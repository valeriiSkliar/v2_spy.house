@extends('layouts.authorized')

@section('page-content')
<x-services.show.back-to-list-button :route="'services.index'" :text="'To the list of services'" />
<x-services.show.details-block :service="$service" :isPromo="$service['code']" :userRating="$service['userRating']" />

<x-services.show.related-services :relatedServices="$relatedServices" :title="'Offers from other companies'" />
@endsection

{{-- @section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle read more/less
        const toggleButtons = document.querySelectorAll('.js-toggle-txt');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const container = this.closest('.hidden-txt');
                container.classList.toggle('is-open');

                if (container.classList.contains('is-open')) {
                    this.textContent = this.dataset.hide || 'Скрыть';
                } else {
                    this.textContent = this.dataset.show || 'Читать больше';
                }
            });
        });

        // Toggle promo code
        const promoCodeButtons = document.querySelectorAll('.js-toggle-code');
        promoCodeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const container = this.closest('.single-market__code');
                container.classList.toggle('is-open');

                if (container.classList.contains('is-open')) {
                    this.textContent = 'Скрыть промокод';
                } else {
                    this.textContent = 'Показать промокод';
                }
            });
        });

        // Copy to clipboard
        const copyButtons = document.querySelectorAll('.btn-copy');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.form-item__field').querySelector('input');
                input.select();
                document.execCommand('copy');

                // Show copied indicator
                this.classList.add('copied');
                setTimeout(() => {
                    this.classList.remove('copied');
                }, 2000);
            });
        });

    });
</script>
@endsection --}}
@push('scripts')
@vite(['resources/js/services.js'])
@endpush