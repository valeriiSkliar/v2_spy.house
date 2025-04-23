<div class="c-table">
    <div class="inner">
        <table class="table no-wrap-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ссылка загрузки</th>
                    <th>Дата добавления / Последний показ</th>
                    <th>Источник</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($landings as $landing)
                <tr>
                    <td>{{ $landing['id'] }}</td>
                    <td><a href="{{ $landing['url'] }}" class="table-link icon-link-arrow"><span>{{ $landing['url'] }}</span></a></td>
                    <td><span class="table-date"><span class="icon-calendar"></span> {{ $landing['date'] }}</span></td>
                    <td><span class="table-source">{{ $landing['source'] }}</span></td>
                    <td>
                        <ul class="table-controls justify-content-end">
                            <li><button class="btn-icon icon-download" type="button"></button></li>
                            <li><button class="btn-icon icon-reload" type="button"></button></li>
                            <li><button class="btn-icon icon-remove" type="button"></button></li>
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>