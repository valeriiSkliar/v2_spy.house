<div class="notification-list">
    @foreach($notifications as $notification)

    <x-notification-item
        :id="$notification['id']"
        :read="$notification['read']"
        :date="$notification['date']"
        :title="$notification['title']"
        :content="$notification['content']"
        :hasButton="$notification['hasButton'] ?? false" />
    @endforeach
</div>