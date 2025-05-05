<div class="filter">
    <div class="filter__trigger-mobile">
        <span class="btn-icon _dark _big _filter">
            <span class="icon-filter"></span>
            <span class="icon-up font-24"></span>
        </span>
        Filter
    </div>
    <div class="filter__content">
        <div class="row align-items-end">
            <div class="col-12 col-md-auto flex-grow-1">
                <div class="row">
                    <div class="col-12 col-lg-4 mb-10">
                        <form action="{{ route('services.index') }}" method="GET" id="searchForm">
                            <div class="form-search">
                                <span class="icon-search"></span>
                                <input type="search" name="search" placeholder="Search by Keyword" value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 mb-10">
                        <x-common.base-select
                            id="category-filter"
                            :selected="$selectedCategory"
                            :options="$categoriesOptions" 
                            :placeholder="$categoriesOptionsPlaceholder"
                        />

                    </div>
                    <div class="col-12 col-md-6 col-lg-4 mb-10">

                        <x-common.base-select
                            id="bonuses-filter"
                            :selected="$selectedBonuses"
                            :options="$bonusesOptions"
                            :placeholder="$bonusesOptionsPlaceholder"
                        />
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto mb-10">
                <div class="reset-btn">
                    <a href="{{ route('services.index') }}" class="btn-icon"><span class="icon-clear"></span> <span class="ml-2 d-md-none">Reset</span></a>
                </div>
            </div>
        </div>
    </div>
</div>