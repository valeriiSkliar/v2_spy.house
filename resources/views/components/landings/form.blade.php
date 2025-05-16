@props(['disabled' => false])

<form action="{{ route('website-downloads.store') }}" method="POST" id="add-landing-form" class="row mb-{{ $disabled ? '20' : '10' }}">
    @csrf
    <div class="col-12 col-md-auto flex-grow-1 mb-10">
        <input
            type="text"
            name="url"
            id="url"
            value="{{ old('url') }}"
            placeholder="{{ $disabled ? __('landings.form.waitForDownloads') : __('landings.form.urlPlaceholder') }}"
            class="input-h-50 w-full {{ $errors->has('url') ? 'border border-red-500' : '' }} {{ $disabled ? 'bg-gray-100 cursor-not-allowed' : '' }}"
            maxlength="300"
            {{ $disabled ? 'disabled' : '' }} />
        {{-- <div id="url-counter" class="text-sm text-gray-500 mt-1" style="display: none;">
            <span id="current-length">0</span>/300
        </div> --}}
        @error('url')
        <div class="text-red-600 mt-1 text-sm">{{ $message }}</div>
        @enderror
        @if (session('message') && session('message.type') === 'error' && !$errors->has('url'))
        <div class="text-red-600 mt-1 text-sm">{{ __('messages.' . session('message.description')) }}</div>
        @endif
    </div>
    <div class="col-12 col-md-auto mb-10">
        <button
            type="submit"
            class="btn _flex _green w-100"
            style="height: 50px;"
            {{ $disabled ? 'disabled' : '' }}>
            <span class="icon-download mr-2"></span>
            <span>{{ __('landings.form.submitButton') }}</span>
        </button>
    </div>
</form>

@pushOnce('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlInput = document.getElementById('url');
        const urlCounter = document.getElementById('url-counter');
        const currentLengthSpan = document.getElementById('current-length');

        if (urlInput && urlCounter && currentLengthSpan) {
            const updateCounter = () => {
                const currentLength = urlInput.value.length;
                currentLengthSpan.textContent = currentLength;
                // Show counter only if there is input
                urlCounter.style.display = currentLength > 0 ? 'block' : 'none';
                // Add red border if over limit (optional feature)
                // urlInput.classList.toggle('border-red-500', currentLength > 300);
            };

            urlInput.addEventListener('input', updateCounter);
            // Initial check in case the field is pre-filled (e.g., validation error)
            updateCounter();
        }
    });
</script>
@endPushOnce