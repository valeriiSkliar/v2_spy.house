<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreativesController extends Controller
{
    /**
     * Display the creatives page with different layouts based on the type
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('type', 'push');

        // Validate that the tab is one of the allowed values
        if (! in_array($activeTab, ['push', 'facebook', 'tiktok', 'inpage'])) {
            $activeTab = 'push';
        }

        // Get counts for each creative type (in a real app, these would come from the database)
        $counts = [
            'push' => '170k',
            'inpage' => '3.1k',
            'facebook' => '65.1k',
            'tiktok' => '45.2m',
        ];

        // Mock data for creatives - replace with actual data retrieval later
        $creativesData = $this->getMockCreativesData();
        $creatives = $creativesData[$activeTab] ?? []; // Get data for the active tab

        return view('pages.creatives.index', [
            'activeTab' => $activeTab,
            'counts' => $counts,
            'creatives' => $creatives, // Pass creatives data to the view
            'type' => $activeTab, // Pass type for social partial
        ]);
    }

    /**
     * Generate mock data for different creative types.
     * In a real application, this data would come from a database or service.
     */
    private function getMockCreativesData(): array
    {
        $commonData = [
            'title' => '⚡ What are the pensions the increase? 💰',
            'description' => 'How much did Kazakhstanis begin to receive',
            'network' => 'Push.house',
            'country_code' => 'KZ',
            'country_name' => 'Kazakhstan', // Added for clarity
            'platform' => 'PC',
            'language' => 'English',
            'first_display_date' => 'Mar 02, 2025',
            'last_display_date' => 'Mar 02, 2025',
            'status' => 'Active',
            'redirect_url' => 'track.luxeprofit.pro',
            'download_url' => '#', // Placeholder
            'open_url' => '#',     // Placeholder
        ];

        return [
            'push' => [
                // Push Creative Item 1
                array_merge($commonData, [
                    'id' => 1,
                    'active_days' => 3,
                    'icon' => '/img/th-2.jpg',
                    'image' => '/img/th-3.jpg',
                    'is_favorite' => true,
                    'icon_size' => '7.2 KB', // Example detail data
                    'image_size' => '8.5 KB', // Example detail data
                ]),
                // Push Creative Item 2
                array_merge($commonData, [
                    'id' => 2,
                    'active_days' => 12, // Different active days
                    'status' => 'Was active', // Different status
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob', // Different platform
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                ]),
            ],
            'inpage' => [
                // Inpage Creative Item 1
                array_merge($commonData, [
                    'id' => 3,
                    'active_days' => 3,
                    'icon' => '/img/th-2.jpg', // Inpage uses icon only in the list
                    'is_favorite' => false,
                    'icon_size' => '7.2 KB',
                ]),
                // Inpage Creative Item 2
                array_merge($commonData, [
                    'id' => 4,
                    'active_days' => 5, // Different data
                    'icon' => '/img/th-1.jpg',
                    'is_favorite' => true, // Different data
                    'country_code' => 'UA', // Different data
                    'country_name' => 'Ukraine',
                    'platform' => 'Mob',
                    'icon_size' => '6.8 KB',
                ]),
            ],
            'facebook' => [
                // Facebook Creative Item 1 (Video)
                array_merge($commonData, [
                    'id' => 5,
                    'active_days' => 3,
                    'is_video' => true,
                    'video_preview' => '/img/facebook-2.jpg',
                    'video_url' => '/img/video-3.mp4',
                    'video_duration' => '00:45',
                    'profile_icon' => '/img/icon-1.jpg',
                    'profile_name' => 'Casino Slots', // Title for social
                    'description' => 'Play Crown Casino online and claim up to 100% bonus on your deposit...', // Different description
                    'social_likes' => 285,
                    'social_comments' => 2,
                    'social_shares' => 7,
                    'is_favorite' => false,
                    'redirect_url' => 'https://area71academy.com/trainings/', // Different URL
                    'country_code' => 'BD', // Different country
                    'country_name' => 'Bangladesh',
                ]),
                // Facebook Creative Item 2 (Image)
                array_merge($commonData, [
                    'id' => 6,
                    'active_days' => 5,
                    'is_video' => false,
                    'image' => '/img/facebook-1.jpg', // Image instead of video
                    'profile_icon' => '/img/icon-1.jpg',
                    'profile_name' => 'Another Slot Game',
                    'description' => 'Claim your bonus now! Limited time offer for new players.',
                    'social_likes' => 150,
                    'social_comments' => 5,
                    'social_shares' => 12,
                    'is_favorite' => true,
                    'country_code' => 'UA', // Different country
                    'country_name' => 'Ukraine',
                ]),
            ],
            'tiktok' => [
                // TikTok Creative Item 1 (Video) - Using similar structure to Facebook for example
                array_merge($commonData, [
                    'id' => 7,
                    'active_days' => 2,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-1.jpg', // Example path
                    'video_url' => '/img/video-1.mp4', // Example path
                    'video_duration' => '00:15',
                    'profile_icon' => '/img/icon-2.jpg', // Example path
                    'profile_name' => 'TikTok Fun',
                    'description' => 'Check out this cool new trend!',
                    'social_likes' => 10200, // TikTok usually has higher numbers
                    'social_comments' => 150,
                    'social_shares' => 300,
                    'is_favorite' => false,
                    'country_code' => 'US', // Different country
                    'country_name' => 'United States',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 8,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg', // Example path
                    'video_url' => '/img/video-2.mp4', // Example path
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg', // Example path
                    'profile_name' => 'Gaming Clips',
                    'description' => 'Epic win compilation!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB', // Different country
                    'country_name' => 'United Kingdom',
                ]),
            ],
        ];
    }
}
