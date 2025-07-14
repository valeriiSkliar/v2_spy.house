@props(['initialTabs' => [], 'tabOptions' => [], 'tabsTranslations' => []])
<div class="vue-component-wrapper" data-vue-component="CreativesTabsComponent" data-vue-props='{
        "initialTabs": {{ json_encode($initialTabs) }},
        "tabOptions": {{ json_encode($tabOptions) }},
        "translations": {{ json_encode($tabsTranslations) }}
    }'>
    <div class="tabs-placeholder" data-vue-placeholder>
        <div class="filter-push">
            <!-- Placeholder для вкладок -->
            <div
                class="filter-push__item placeholder-shimmer {{ ($tabOptions['activeTab'] ?? 'push') === 'push' ? 'active' : '' }}">
                Push
                @if(($tabOptions['tabCounts']['push'] ?? 0) > 0)
                <span class="filter-push__count placeholder-shimmer">{{
                    App\Helpers\format_count($tabOptions['tabCounts']['push'] ??
                    0) }}</span>
                @endif
            </div>
            <div
                class="filter-push__item placeholder-shimmer {{ ($tabOptions['activeTab'] ?? 'push') === 'inpage' ? 'active' : '' }}">
                Inpage
                @if(($tabOptions['tabCounts']['inpage'] ?? 0) > 0)
                <span class="filter-push__count placeholder-shimmer">{{
                    App\Helpers\format_count($tabOptions['tabCounts']['inpage']
                    ?? 0) }}</span>
                @endif
            </div>
            <div
                class="filter-push__item placeholder-shimmer {{ ($tabOptions['activeTab'] ?? 'push') === 'facebook' ? 'active' : '' }}">
                Facebook
                @if(($tabOptions['tabCounts']['facebook'] ?? 0) > 0)
                <span class="filter-push__count placeholder-shimmer">{{
                    App\Helpers\format_count($tabOptions['tabCounts']['facebook'] ?? 0) }}</span>
                @endif
            </div>
            <div
                class="filter-push__item placeholder-shimmer {{ ($tabOptions['activeTab'] ?? 'push') === 'tiktok' ? 'active' : '' }}">
                Tiktok
                @if(($tabOptions['tabCounts']['tiktok'] ?? 0) > 0)
                <span class="filter-push__count placeholder-shimmer">{{
                    App\Helpers\format_count($tabOptions['tabCounts']['tiktok']
                    ?? 0) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Стили для placeholder компонента вкладок */
    .tabs-placeholder .filter-push {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .tabs-placeholder .filter-push__item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border: none;
        background: none;
        font-size: 16px;
        font-weight: 500;
        color: #78888F;
        border-bottom: 2px solid transparent;
        opacity: 0.7;
        pointer-events: none;
    }

    .tabs-placeholder .filter-push__item.active {
        border-bottom-color: #3DC98A;
        color: #3DC98A;
    }

    .tabs-placeholder .filter-push__count {
        background: #F3F5F6;
        border-radius: 5px;
        font-size: 12px;
        color: #6E8087;
        padding: 3px 6px;
        font-weight: 400;
        min-width: 30px;
        text-align: center;
    }

    /* Анимация shimmer для placeholder */
    .placeholder-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    @media (max-width: 768px) {
        .tabs-placeholder .filter-push {
            gap: 10px;
        }

        .tabs-placeholder .filter-push__item {
            padding: 10px 12px;
            font-size: 14px;
        }

        .tabs-placeholder .filter-push__count {
            font-size: 11px;
            padding: 2px 4px;
        }
    }
</style>