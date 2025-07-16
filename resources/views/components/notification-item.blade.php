@props(['id' => 0, 'read' => 0, 'date' => '', 'title' => '', 'content' => ''])

<div class="notification-item {{ $read ? '_read' : '' }}">
    <div class="notification-item__label">{{ $read ? __('notifications.notification_item.read') :
        __('notifications.notification_item.new') }}</div>
    <div class="notification-item__date">{{ $date }}</div>
    <div class="row align-items-start">
        <div class="col-12 col-lg-auto flex-grow-1 w-lg-1 mb-10">
            <h3>{{ $title }}</h3>
            <p>{!! $content !!}</p>
        </div>
        {{-- <div class="col-12 col-lg-auto mb-10">
            @if(!$read)
            <div class="notification-item__btn">
                <button data-id="{{ $id }}" data-read="{{ $read }}"
                    data-url="{{ route('notifications.markAsRead', $id) }}" type="submit"
                    class="btn _flex _border-green _medium">{{ __('notifications.acquainted') }}</button>
            </div>
            @endif
        </div> --}}
    </div>
</div>