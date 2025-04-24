<div class="search-form">
    <div class="container">
        <form action="{{ route('blog.search') }}" method="GET">
            <div class="form-search">
                <span class="icon-search font-20"></span>
                <input type="search" name="q" placeholder="Search by Keyword" class="input-h-57" value="{{ request('q') }}">
                <div class="search-suggestions" style="display: none;">
                    <div class="search-suggestions__content"></div>
                </div>
            </div>
        </form>
    </div>
</div>