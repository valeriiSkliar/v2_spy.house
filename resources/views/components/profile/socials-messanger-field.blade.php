@props(['user', 'label'])

@php
$messenger_type = $user->messenger_type ?? 'telegram';
$messenger_contact = $user->messenger_contact ?? '';
@endphp

<x-common.messenger-field-component name="messenger_contact" messenger-type-name="messenger_type" :label="$label"
    :value="$messenger_contact" :messenger-type="$messenger_type" container-class="form-item mb-20"
    select-id="profile-messanger-select" />