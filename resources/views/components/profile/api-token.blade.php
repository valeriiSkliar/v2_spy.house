@props(['api_token'])
<div class="col-12 col-md-auto mb-10">
    <input id="api_token" name="api_token" type="hidden" value="{{ $api_token }}">
    {{-- Token: <span class="text-danger">{{ $api_token }}</span> --}}
    {{-- <button type="button" id="test-base-token" class="btn btn-black">Test Base Token</button> --}}
    {{-- <button type="button" id="test-base-token2" class="btn btn-black">Test Base Token 2</button> --}}
</div>