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
         data-pagination-container {{-- Маркер для JS --}}
         data-target-selector="#landings-content-wrapper" {{-- Что обновлять --}}
         data-ajax-url="{{ route('landings.list') }}" {{-- Куда делать запрос --}}
         data-filter-form-selector="#landings-sort-form" {{-- Откуда брать фильтры --}}
    >
        {{-- Используем новый шаблон 'components.custom-pagination' --}}
        {{ $landings->appends(request()->except('page'))->links('common.pagination.spy-pagination-default') }}
    </div>
    @endif
@else
    <x-landings.landings-empty-list />
@endif
