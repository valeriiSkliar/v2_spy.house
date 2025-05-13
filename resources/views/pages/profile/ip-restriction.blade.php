@extends('layouts.main')

@section('page-content')

    <h1 class="mb-25">{{ __('profile.ip_restriction.page_title') }}</h1>
    <div class="section profile-settings">
        <x-profile.info-v2-message
        status="ip-restriction-updated" 
        :message="__('profile.ip_restriction.update_success')" 
    />
        <form action="{{ route('profile.update-ip-restriction') }}" method="POST" class="pt-3">
            @csrf
            @method('PUT')
            {{-- <x-profile.info-message 
                :class="'small'"
                :description="__('profile.ip_restriction.info')"
            /> --}}
            <div class="col-lg-4 col-md-6 col-12 mb-20">
                <label class="d-block mb-10">{{ __('profile.ip_restriction.allowed_ip_addresses_label') }}</label>
                <textarea name="ip_restrictions" class="auto-resize" rows="5" placeholder="{{ __('profile.ip_restriction.allowed_ip_addresses_placeholder') }}">{{ is_array(auth()->user()->ip_restrictions) ? implode("\n", auth()->user()->ip_restrictions) : auth()->user()->ip_restrictions }}</textarea>
                @error('ip_restrictions')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-lg-4 col-md-6 col-12 mb-20">
                <label class="d-block mb-10">{{ __('profile.ip_restriction.your_password_label') }}</label>
                <input type="password" name="password" class="input-h-57" autocomplete="current-password">
                @error('password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <x-profile.submit-button :label="__('profile.save_button')" />
        </form>
    </div>
@endsection 

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.auto-resize');
        
        textareas.forEach(textarea => {
            // Установка начальной высоты при загрузке страницы
            adjustHeight(textarea);
            
            // Добавление слушателя события ввода
            textarea.addEventListener('input', function() {
                adjustHeight(this);
            });
        });
        
        function adjustHeight(element) {
            // Сброс высоты для корректного расчета
            element.style.height = 'auto';
            // Установка новой высоты на основе содержимого
            element.style.height = element.scrollHeight + 'px';
        }
    });
</script>
@endpush 