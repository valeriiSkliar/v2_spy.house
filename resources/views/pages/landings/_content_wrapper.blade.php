{{--
    This partial accepts:
    - $landings: Collection of WebsiteDownloadMonitor with pagination
    - $sortOptions: Array of sorting options
    - $paginationOptions: Array of pagination options
    - $currentSort: Current sorting parameters
    - $currentPerPage: Current number of items per page
    - $viewConfig: View configuration from BaseLandingsPageController
--}}

@if($landings->isNotEmpty())
    <x-landings.table :landings="$landings" :viewConfig="$viewConfig" />
    <div class="pagination-container mt-4">
        {{ $landings->appends(request()->only(['sort_by', 'sort_direction', 'per_page']))->links('components.pagination') }}
    </div>
@else
    <x-landings.landings-empty-list />
@endif 