@props(['formId' => null, 'label' => '', 'id' => null, 'action' => null])

<div class="mb-20">
    <button form="{{ $formId }}" type="submit" class="btn _flex _green _big min-200 w-mob-100" id="{{ $id }}"
        data-action="{{ $action }}">{{ $label }}</button>
</div>