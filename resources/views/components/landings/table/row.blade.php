@props(['landing' => []])
<tr>
    <td><a target="_blank" href="{{ $landing['url'] }}" class="table-link icon-link-arrow"><span>{{ $landing['url'] }}</span></a></td>
    <td><span class="table-date"><span class="icon-calendar"></span> {{ $landing['started_at'] ?? $landing['created_at'] }}</span></td>
    <td>
        <ul class="table-controls justify-content-end">
            @if($landing['status'] !== 'completed')
            <li class="landing-status-icon" data-status="{{ $landing['status'] }}">
                <x-landings.status-icon status="{{ $landing['status'] }}" />
            </li>
            @endif
            @if($landing['status'] === 'completed')
            <li>
                <button type="button" class="btn-icon icon-download download-landing-button" data-id="{{ $landing->id }}"></button>
            </li>
            @endif
            <li>
                <button @if($landing['status']==='pending' ) disabled @endif type="button"
                    class="btn-icon icon-remove delete-landing-button"
                    data-id="{{ $landing->id }}"
                    data-confirm="{{ __('landings.table.confirmDelete.message') }}"
                    data-confirm-title="{{ __('landings.table.confirmDelete.title') }}"
                    data-confirm-btn="{{ __('landings.table.confirmDelete.confirmButton') }}"
                    data-confirm-cancel="{{ __('landings.table.confirmDelete.cancelButton') }}"
                    data-delete-url="{{ route('landings.destroy', ['landing' => $landing->id, 'page' => request()->input('page', 1), 'per_page' => request()->input('per_page', 12)]) }}">
                </button>
            </li>
        </ul>
    </td>
</tr>