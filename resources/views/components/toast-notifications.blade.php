@if (session('toasts'))
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    @foreach (session('toasts') as $toast)
    <div class="toast align-items-center text-white bg-{{ $toast['type'] === 'error' ? 'danger' : ($toast['type'] === 'warning' ? 'warning' : ($toast['type'] === 'success' ? 'success' : 'info')) }} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="{{ $toast['options']['duration'] ?? 5000 }}">
        <div class="d-flex">
            <div class="toast-body">
                {{-- Assuming message is already translated or is a plain string --}}
                {{ $toast['message'] }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @endforeach
</div>

@pushOnce('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast-container .toast'))
        var toastList = toastElList.map(function(toastEl) {
            // Ensure toast is not already shown (important if page reloads quickly)
            if (!toastEl.classList.contains('show') && !toastEl.classList.contains('showing')) {
                return new bootstrap.Toast(toastEl)
            }
            return null;
        });
        toastList.forEach(function(toast) {
            if (toast) {
                toast.show();
            }
        });
    });
</script>
@endPushOnce
@endif