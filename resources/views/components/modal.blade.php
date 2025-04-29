@props([
'id' => 'dynamicModal', // Default ID, can be overridden
'titleId' => 'dynamicModalLabel',
'size' => '' // e.g., 'modal-sm', 'modal-lg', 'modal-xl'
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $titleId }}" aria-hidden="true">
    <div class="modal-dialog {{ $size }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $titleId }}">{{-- Title will be set by JS --}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $slot }} {{-- Modal content goes here --}}
            </div>
            @isset($footer)
            <div class="modal-footer">
                {{ $footer }} {{-- Optional footer content --}}
            </div>
            @endisset
        </div>
    </div>
</div>