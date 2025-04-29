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
                            <li><button class="btn-icon icon-download" type="button"></button></li>
                            <li><button class="btn-icon icon-reload" type="button"></button></li>
                            <li>
                                <button type="button"
                                    class="btn-icon icon-remove delete-landing-button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteLandingModal"
                                    data-landing-id="{{ $landing->id }}"
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