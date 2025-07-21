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

    @if ($landings->hasPages())
    <div class="pagination-controls mt-4"
         data-pagination-container {{-- JS marker --}}
         data-target-selector="#landings-content-wrapper" {{-- What to update --}}
         data-ajax-url="{{ route('landings.list.ajax') }}" {{-- Where to send the request --}}
         data-filter-form-selector="#landings-sort-form" {{-- Where to get filters from --}}
    >
        {{-- Using the new 'components.custom-pagination' template --}}
        {{ $landings->appends(request()->except('page'))->links('common.pagination.spy-pagination-default') }}
    </div>
    @endif
@else
    <x-empty-landings />
@endif
