@props(['id' => 0, 'read' => false, 'date' => '', 'title' => '', 'content' => '', 'hasButton' => false])

<div class="notification-item {{ $read ? '_read' : '' }}">
    <div class="notification-item__label">{{ $read ? 'Read' : 'New' }}</div>
    <div class="notification-item__date">{{ $date }}</div>
    <div class="row align-items-start">
        <div class="col-12 col-lg-auto flex-grow-1 w-lg-1 mb-10">
            <h3>{{ $title }}</h3>
            <p>{!! $content !!}</p>
        </div>
        @if(!$read && $hasButton)
        <div class="col-12 col-lg-auto mb-10">
            <div class="notification-item__btn">
                <form action="{{ route('notifications.markAsRead', $id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn _flex _border-green _medium">{{ __('notifications.acquainted') }}</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>