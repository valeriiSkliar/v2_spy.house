@if(session('message'))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast hide bg-{{ session('message.type') === 'error' ? 'danger' : 'success' }} text-white" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="{{ session('message.duration', 3000) }}">
        <div class="toast-header">
            {{-- <img src="..." class="rounded me-2" alt="..."> --}}
            <strong class="me-auto">{{ __('messages.' . session('message.title')) }}</strong>
            {{-- <small>11 mins ago</small> --}}
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            {{ __('messages.' . session('message.description'), session('message.description_params', [])) }}
        </div>
    </div>
</div>
@endif

<div class="c-table">
    <div class="inner">
        <table class="table no-wrap-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ссылка загрузки</th>
                    <th>Дата добавления</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($landings as $landing)
                <tr>
                    <td>{{ $landing['id'] }}</td>
                    <td><a href="{{ $landing['url'] }}" class="table-link icon-link-arrow"><span>{{ $landing['url'] }}</span></a></td>
                    <td><span class="table-date"><span class="icon-calendar"></span> {{ $landing['started_at'] ?? $landing['created_at'] }}</span></td>
                    <td>
                        <ul class="table-controls justify-content-end">
                            <li>
                                <a href="{{ route('landings.download', $landing->id) }}" class="btn-icon icon-download"></a>
                            </li>
                            <li><button class="btn-icon icon-reload" type="button"></button></li>
                            <li>
                                <button type="button"
                                    class="btn-icon icon-remove delete-landing-button"
                                    data-confirm="Are you sure you want to delete this landing? This action cannot be undone."
                                    data-confirm-title="Confirm Deletion"
                                    data-confirm-btn="Delete"
                                    data-confirm-cancel="Cancel"
                                    data-delete-url="{{ route('landings.destroy', ['landing' => $landing->id, 'page' => request()->input('page', 1), 'per_page' => request()->input('per_page', 12)]) }}">
                                </button>
                            </li>
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('liveToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>
@endpush