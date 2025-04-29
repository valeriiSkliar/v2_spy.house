@props([
'id' => 'dynamicModal',
'title' => '',
'size' => '', // '', 'sm', 'lg', 'xl'
'closeButton' => true,
'staticBackdrop' => false,
'centered' => false
])

<div class="modal fade"
    id="{{ $id }}"
    tabindex="-1"
    aria-labelledby="{{ $id }}-label"
    aria-hidden="true"
    @if($staticBackdrop) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
    <div class="modal-dialog {{ $size ? 'modal-'.$size : '' }} {{ $centered ? 'modal-dialog-centered' : '' }}">
        <div class="modal-content">
            @if($title || $closeButton)
            <div class="modal-header">
                @if($title)
                <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                @endif

                @if($closeButton)
                <button type="button" class="btn-icon _gray btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><span class="icon-x"></span></span>
                </button>
                @endif
            </div>
            @endif

            <div class="modal-body">
                {{ $slot }}
            </div>

            @if(isset($footer))
            <div class="modal-footer">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>