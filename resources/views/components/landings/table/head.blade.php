@props(['headers' => []])
<thead>
    <tr>
        @foreach($headers as $header)
        <th>{{ __($header) }}</th>
        @endforeach
    </tr>
</thead>