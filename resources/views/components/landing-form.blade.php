@props(['disabled' => false])

<form class="row mb-{{ $disabled ? '20' : '10' }}">
    <div class="col-12 col-md-auto flex-grow-1 mb-10">
        <input type="text" class="input-h-50" placeholder="Enter the link to download the Landing Page">
    </div>
    <div class="col-12 col-md-auto mb-10">
        <button type="submit" class="btn _flex _green w-100" {{ $disabled ? 'disabled' : '' }}>
            <span class="icon-download mr-2"></span>Download
        </button>
    </div>
</form>