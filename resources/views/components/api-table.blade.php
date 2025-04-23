@props(['headers' => [], 'rows' => [], 'transparentHeader' => false])

<div class="c-table">
    <div class="inner">
        <table class="table {{ $transparentHeader ? 'thead-transparent' : '' }}">
            <thead>
                <tr>
                    @foreach($headers as $header)
                    <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>