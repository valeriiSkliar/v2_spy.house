<div class="creatives-list _social">
    <div class="creatives-list__items">
        @forelse($creatives as $creative)
        @include('components.creatives.creative-item-social', [
        'type' => $type,
        'image' => $creative['is_video'] ? $creative['video_preview'] : $creative['image'],
        'hasVideo' => $creative['is_video'] ?? false,
        'videoSrc' => $creative['video_url'] ?? null,
        'duration' => $creative['video_duration'] ?? '00:45',
        'icon' => $creative['profile_icon'] ?? '/img/icon-1.jpg',
        'title' => $creative['profile_name'] ?? 'Profile Name',
        'description' => $creative['description'] ?? '',
        'likes' => $creative['social_likes'] ?? 0,
        'comments' => $creative['social_comments'] ?? 0,
        'shares' => $creative['social_shares'] ?? 0,
        'activeDays' => $creative['active_days'] ?? 1,
        'flagIcon' => '/img/flags/' . $creative['country_code'] . '.svg',
        'controls' => false,
        'showNewTab' => false
        ])
        @empty
        <p>{{ __('creatives.no-data') }}</p>
        @endforelse
    </div>

    @if(isset($creatives[0]))
    @include('components.creatives.social-details', [
    'type' => $type,
    'isFavorite' => $creatives[0]['is_favorite'] ?? false,
    'videoImage' => $creatives[0]['is_video'] ? $creatives[0]['video_preview'] : $creatives[0]['image'],
    'duration' => $creatives[0]['video_duration'] ?? '00:45',
    'videoSrc' => $creatives[0]['video_url'] ?? '/img/video-2.mp4',
    'title' => $creatives[0]['title'] ?? '',
    'longDescription' => $creatives[0]['description'] ?? '',
    'likes' => number_format($creatives[0]['social_likes'] ?? 0),
    'comments' => $creatives[0]['social_comments'] ?? 0,
    'shares' => $creatives[0]['social_shares'] ?? 0,
    'trackingLink' => $creatives[0]['redirect_url'] ?? '#',
    'country' => $creatives[0]['country_name'] ?? '',
    'language' => $creatives[0]['language'] ?? 'English',
    'firstDate' => $creatives[0]['first_display_date'] ?? '',
    'lastDate' => $creatives[0]['last_display_date'] ?? '',
    'status' => $creatives[0]['status'] === 'Active'
    ])
    @endif
</div>