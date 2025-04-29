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
                    <td><a target="_blank" href="{{ $landing['url'] }}" class="table-link icon-link-arrow"><span>{{ $landing['url'] }}</span></a></td>
                    <td><span class="table-date"><span class="icon-calendar"></span> {{ $landing['started_at'] ?? $landing['created_at'] }}</span></td>
                    <td>
                        <ul class="table-controls justify-content-end">
                            @if($landing['status'] !== 'completed')
                            <li><x-frontend.status-icon status="{{ $landing['status'] }}" /></li>
                            @endif
                            <!-- <li><button class="btn-icon icon-reload {{ $landing['status'] === 'pending' ? 'text-warning' : '' }}" type="button"></button></li> -->
                            @if($landing['status'] === 'completed')
                            <li>
                                <a href="{{ route('landings.download', $landing->id) }}" class="btn-icon icon-download"></a>
                            </li>
                            @endif
                            <li>
                                <button @if($landing['status']==='pending' ) disabled @endif type="button"
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