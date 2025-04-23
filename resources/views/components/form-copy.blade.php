@props(['value' => '', 'green' => false])

<div class="form-copy {{ $green ? '_green' : '' }}">
    <input class="input-h-57" type="text" value="{{ $value }}" readonly>
    <button class="btn-icon _gray2 js-copy">
        <span class="icon-copy2"></span>
        <span class="icon-check d-none"></span>
    </button>
</div>