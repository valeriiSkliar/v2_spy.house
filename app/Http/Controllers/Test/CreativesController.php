<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreativesController extends Controller
{
    /**
     * Display the creatives page with different layouts based on the tabs
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tabs', 'push');

        // Validate that the tab is one of the allowed values
        if (! in_array($activeTab, ['push', 'facebook', 'tiktok', 'inpage'])) {
            $activeTab = 'push';
        }

        // Get counts for each creative tabs (in a real app, these would come from the database)
        $counts = [
            'push' => '170k',
            'inpage' => '3.1k',
            'facebook' => '65.1k',
            'tiktok' => '45.2m',
        ];

        // Опции для селекта "На странице"
        $perPageOptions = [
            ['value' => '12', 'order' => '1', 'label' => '12'],
            ['value' => '24', 'order' => '2', 'label' => '24'],
            ['value' => '48', 'order' => '3', 'label' => '48'],
            ['value' => '96', 'order' => '4', 'label' => '96'],
        ];

        // Получаем текущее значение perPage из запроса или устанавливаем по умолчанию
        $perPage = $request->get('per_page', '12');

        // Mock data for creatives - replace with actual data retrieval later
        $creativesData = $this->getMockCreativesData();
        $creatives = $creativesData[$activeTab] ?? []; // Get data for the active tab

        return view('pages.creatives.index', [
            'activeTab' => $activeTab,
            'counts' => $counts,
            'creatives' => $creatives, // Pass creatives data to the view
            'tabs' => $activeTab, // Pass tabs for social partial
            'perPageOptions' => $perPageOptions, // Опции для селекта "На странице"
            'perPage' => $perPage, // Текущее значение количества элементов на странице
        ]);
    }

    /**
     * Generate mock data for different creative tabs.
     * In a real application, this data would come from a database or service.
     */
    /**
     * Generate mock data for different creative tabs.
     * In a real application, this data would come from a database or service.
     */
    private function getMockCreativesData(): array
    {
        $commonData = [
            'title' => '🔥 Discover the latest investment trends! 💸',
            'description' => 'Unlock secrets to financial success today',
            'network' => 'Push.house',
            'country_code' => 'KZ',
            'country_name' => 'Kazakhstan',
            'platform' => 'PC',
            'language' => 'English',
            'first_display_date' => 'Mar 02, 2025',
            'last_display_date' => 'Mar 02, 2025',
            'status' => 'Active',
            'redirect_url' => 'track.luxeprofit.pro',
            'download_url' => '#',
            'open_url' => '#',
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
                    'icon_size' => '7.2 KB',
                    'image_size' => '8.5 KB',
                    'title' => '💡 Boost your savings now! 🚀',
                    'description' => 'Top tips for growing your wealth in 2025',
                ]),
                // Push Creative Item 2
                array_merge($commonData, [
                    'id' => 2,
                    'active_days' => 12,
                    'status' => 'Was active',
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob',
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                    'title' => '📈 Maximize your profits today! 💰',
                    'description' => 'Learn expert strategies for financial growth',
                ]),
                // Push Creative Item 3
                array_merge($commonData, [
                    'id' => 3,
                    'active_days' => 12,
                    'status' => 'Was active',
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob',
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                    'title' => '📈 Maximize your profits today! 💰',
                    'description' => 'Learn expert strategies for financial growth',
                ]),
                // Push Creative Item 3
                array_merge($commonData, [
                    'id' => 5,
                    'active_days' => 12,
                    'status' => 'Was active',
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob',
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                    'title' => '📈 Maximize your profits today! 💰',
                    'description' => 'Learn expert strategies for financial growth',
                ]),
                // Push Creative Item 3
                array_merge($commonData, [
                    'id' => 4,
                    'active_days' => 12,
                    'status' => 'Was active',
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob',
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                    'title' => '📈 Maximize your profits today! 💰',
                    'description' => 'Learn expert strategies for financial growth',
                ]),
                // Push Creative Item 3
                array_merge($commonData, [
                    'id' => 3,
                    'active_days' => 12,
                    'status' => 'Was active',
                    'icon' => '/img/th-1.jpg',
                    'image' => '/img/th-4.jpg',
                    'platform' => 'Mob',
                    'is_favorite' => false,
                    'icon_size' => '6.8 KB',
                    'image_size' => '9.1 KB',
                    'title' => '📈 Maximize your profits today! 💰',
                    'description' => 'Learn expert strategies for financial growth',
                ]),
            ],
            'inpage' => [
                // Inpage Creative Item 1
                array_merge($commonData, [
                    'id' => 3,
                    'active_days' => 3,
                    'icon' => '/img/th-2.jpg',
                    'is_favorite' => false,
                    'icon_size' => '7.2 KB',
                    'title' => '🌟 Start your journey to riches! 💎',
                    'description' => 'Explore new ways to earn big',
                ]),
                // Inpage Creative Item 2
                array_merge($commonData, [
                    'id' => 4,
                    'active_days' => 5,
                    'icon' => '/img/th-1.jpg',
                    'is_favorite' => true,
                    'country_code' => 'UA',
                    'country_name' => 'Ukraine',
                    'platform' => 'Mob',
                    'icon_size' => '6.8 KB',
                    'title' => '🎯 Achieve financial freedom! 🤑',
                    'description' => 'Join thousands winning with our platform',
                ]),
                // Inpage Creative Item 3
                array_merge($commonData, [
                    'id' => 6,
                    'active_days' => 5,
                    'icon' => '/img/th-1.jpg',
                    'is_favorite' => true,
                    'country_code' => 'UA',
                    'country_name' => 'Ukraine',
                    'platform' => 'Mob',
                    'icon_size' => '6.8 KB',
                    'title' => '🎯 Achieve financial freedom! 🤑',
                    'description' => 'Join thousands winning with our platform',
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
                    'profile_name' => 'Wealth Masters',
                    'description' => 'Join our online trading academy and earn up to 200% returns!',
                    'social_likes' => 285,
                    'social_comments' => 2,
                    'social_shares' => 7,
                    'is_favorite' => false,
                    'redirect_url' => 'https://area71academy.com/trainings/',
                    'country_code' => 'BD',
                    'country_name' => 'Bangladesh',
                    'title' => '🚀 Skyrocket your income! 💵',
                ]),
                // Facebook Creative Item 2 (Image)
                array_merge($commonData, [
                    'id' => 6,
                    'active_days' => 5,
                    'is_video' => false,
                    'image' => '/img/facebook-1.jpg',
                    'profile_icon' => '/img/icon-1.jpg',
                    'profile_name' => 'Crypto Kings',
                    'description' => 'Sign up today for exclusive crypto trading bonuses!',
                    'social_likes' => 150,
                    'social_comments' => 5,
                    'social_shares' => 12,
                    'is_favorite' => true,
                    'country_code' => 'UA',
                    'country_name' => 'Ukraine',
                    'title' => '💸 Join the crypto revolution! 📊',
                ]),
                // Facebook Creative Item 2 (Image)
                array_merge($commonData, [
                    'id' => 7,
                    'active_days' => 5,
                    'is_video' => false,
                    'image' => '/img/facebook-1.jpg',
                    'profile_icon' => '/img/icon-1.jpg',
                    'profile_name' => 'Crypto Kings',
                    'description' => 'Sign up today for exclusive crypto trading bonuses!',
                    'social_likes' => 150,
                    'social_comments' => 5,
                    'social_shares' => 12,
                    'is_favorite' => true,
                    'country_code' => 'UA',
                    'country_name' => 'Ukraine',
                    'title' => '💸 Join the crypto revolution! 📊',
                ]),
                // Facebook Creative Item 2 (Image)
                array_merge($commonData, [
                    'id' => 8,
                    'active_days' => 5,
                    'is_video' => false,
                    'image' => '/img/facebook-1.jpg',
                    'profile_icon' => '/img/icon-1.jpg',
                    'profile_name' => 'Crypto Kings',
                    'description' => 'Sign up today for exclusive crypto trading bonuses!',
                    'social_likes' => 150,
                    'social_comments' => 5,
                    'social_shares' => 12,
                    'is_favorite' => true,
                    'country_code' => 'UA',
                    'country_name' => 'Ukraine',
                    'title' => '💸 Join the crypto revolution! 📊',
                ]),
            ],
            'tiktok' => [
                // TikTok Creative Item 1 (Video)
                array_merge($commonData, [
                    'id' => 7,
                    'active_days' => 2,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-1.jpg',
                    'video_url' => '/img/video-1.mp4',
                    'video_duration' => '00:15',
                    'profile_icon' => '/img/icon-2.jpg',
                    'profile_name' => 'Money Moves',
                    'description' => 'Viral hacks to make money online!',
                    'social_likes' => 10200,
                    'social_comments' => 150,
                    'social_shares' => 300,
                    'is_favorite' => false,
                    'country_code' => 'US',
                    'country_name' => 'United States',
                    'title' => '💰 Cash in on this trend! 🔥',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 9,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 9,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 10,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 11,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 12,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 13,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 14,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 15,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 16,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),

                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 17,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),

                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 18,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
                // TikTok Creative Item 2 (Video)
                array_merge($commonData, [
                    'id' => 19,
                    'active_days' => 7,
                    'is_video' => true,
                    'video_preview' => '/img/tiktok-2.jpg',
                    'video_url' => '/img/video-2.mp4',
                    'video_duration' => '00:30',
                    'profile_icon' => '/img/icon-3.jpg',
                    'profile_name' => 'Trend Setters',
                    'description' => 'Insane money-making tips you need NOW!',
                    'social_likes' => 55000,
                    'social_comments' => 400,
                    'social_shares' => 950,
                    'is_favorite' => true,
                    'country_code' => 'GB',
                    'country_name' => 'United Kingdom',
                    'title' => '🎉 Millionaires mentor you! 💡',
                ]),
            ],
        ];
    }

    /**
     * Get tab counts for creatives API
     * Returns the count of creatives for each tab type
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tabCounts()
    {
        // TODO: add cache in production app (redis / update cache 10 times a day)
        try {
            $creativesData = $this->getMockCreativesData();

            // Count creatives for each tab
            $tabCounts = [
                'push' => count($creativesData['push'] ?? []),
                'inpage' => count($creativesData['inpage'] ?? []),
                'facebook' => count($creativesData['facebook'] ?? []),
                'tiktok' => count($creativesData['tiktok'] ?? []),
            ];

            return response()->json($tabCounts);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load tab counts'], 500);
        }
    }

    /**
     * API endpoint for loading creatives with pagination and filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex(Request $request)
    {
        try {
            $tab = $request->input('tab', 'push');
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 12)));

            // Validate tab
            if (! in_array($tab, ['push', 'inpage', 'facebook', 'tiktok'])) {
                $tab = 'push';
            }

            $creativesData = $this->getMockCreativesData();
            $allCreatives = $creativesData[$tab] ?? [];

            // Apply search filter if provided
            $search = $request->input('search', '');
            if (! empty($search)) {
                $allCreatives = array_filter($allCreatives, function ($creative) use ($search) {
                    return stripos($creative['title'], $search) !== false ||
                        stripos($creative['description'], $search) !== false;
                });
                $allCreatives = array_values($allCreatives); // reindex array
            }

            // Apply category filter if provided
            $category = $request->input('category', '');
            if (! empty($category)) {
                $allCreatives = array_filter($allCreatives, function ($creative) use ($category) {
                    return isset($creative['category']) && $creative['category'] === $category;
                });
                $allCreatives = array_values($allCreatives);
            }

            // Apply sort filter if provided
            $sortBy = $request->input('sortBy', 'created_at');
            $sortOrder = $request->input('sortOrder', 'desc');

            // Apply date filters if provided
            $dateFrom = $request->input('dateFrom', '');
            $dateTo = $request->input('dateTo', '');
            // Note: Date filtering would be implemented here in a real app

            // Calculate pagination
            $totalCount = count($allCreatives);
            $totalPages = ceil($totalCount / $perPage);
            $currentPage = min($page, max(1, $totalPages));

            // Get page data
            $offset = ($currentPage - 1) * $perPage;
            $pageData = array_slice($allCreatives, $offset, $perPage);

            // Get tab counts for all tabs
            $tabCounts = [
                'push' => count($creativesData['push'] ?? []),
                'inpage' => count($creativesData['inpage'] ?? []),
                'facebook' => count($creativesData['facebook'] ?? []),
                'tiktok' => count($creativesData['tiktok'] ?? []),
            ];

            return response()->json([
                'data' => $pageData,
                'current_page' => $currentPage,
                'last_page' => $totalPages,
                'per_page' => $perPage,
                'total' => $totalCount,
                'from' => $totalCount > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalCount),
                'tab_counts' => $tabCounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load creatives',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
