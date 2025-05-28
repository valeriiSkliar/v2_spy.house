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
            <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                <button class="btn-icon _dark _big _filter js-toggle-detailed-filtering">
                    <span class="icon-filter"></span>
                    <span class="icon-up font-24"></span>
                </button>
            </div>
            <div class="col-12 col-md-auto flex-grow-1 w-md-1">
                <div class="row">
                    <div class="col-12 col-lg-4 mb-10">
                        <div class="form-search">
                            <span class="icon-search"></span>
                            <input type="search"
                                placeholder="{{ __('select-options.placeholders.search_by_keyword') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        <div class="base-select">
                            <div class="base-select__trigger"><span class="base-select__value">Country</span><span
                                    class="base-select__arrow"></span></div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected">All Categories</li>
                                <li class="base-select__option">Advertising Networks</li>
                                <li class="base-select__option">Affiliate Programs</li>
                                <li class="base-select__option">Trackers</li>
                                <li class="base-select__option">Hosting</li>
                                <li class="base-select__option">Domain Registrars</li>
                                <li class="base-select__option">SPY Services</li>
                                <li class="base-select__option">Proxy and VPN Services</li>
                                <li class="base-select__option">Anti-detection Browsers</li>
                                <li class="base-select__option">Account Purchase and Rental</li>
                                <li class="base-select__option">Purchase and Rental of Applications</li>
                                <li class="base-select__option">Notification and Newsletter Services</li>
                                <li class="base-select__option">Payment Services</li>
                                <li class="base-select__option">Other Services and Utilities</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        <div class="filter-date-select">
                            <div data-v-c57906f2="" class="date-picker-container" name="dateCreation">
                                <div data-v-c57906f2="" id="dateCreation" class="date-select-field " role="button"
                                    aria-expanded="true"><span data-v-c57906f2="">Date of creation</span><span
                                        data-v-c57906f2="" class="dropdown-arrow is-open"></span></div>
                                <div data-v-c57906f2="" class="date-options-dropdown" style="display: none;">
                                    <div data-v-c57906f2="" class="preset-ranges"><button data-v-c57906f2=""
                                            class="range-option">Today</button><button data-v-c57906f2=""
                                            class="range-option">Yesterday</button><button data-v-c57906f2=""
                                            class="range-option">Last 7 days</button><button data-v-c57906f2=""
                                            class="range-option">Last 30 days</button><button data-v-c57906f2=""
                                            class="range-option">This month</button><button data-v-c57906f2=""
                                            class="range-option">Last month</button><button data-v-c57906f2=""
                                            class="range-option active">Custom Range</button></div>
                                    <!---->
                                </div>
                            </div>
                            <span class="icon-date"></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        <div class="base-select">
                            <div class="base-select__trigger"><span class="base-select__value">Sort by</span><span
                                    class="base-select__arrow"></span></div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected">By creation date</li>
                                <li class="base-select__option">By days of activity</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                <div class="reset-btn">
                    <button class="btn-icon"><span class="icon-clear"></span> <span
                            class="ml-2 d-md-none">Reset</span></button>
                </div>
            </div>
        </div>
        <div class="filter__detailed" style="display: none;">
            <div class="filter__title">Detailed filtering</div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-date-select">
                        <div data-v-c57906f2="" class="date-picker-container" name="dateCreation">
                            <div data-v-c57906f2="" id="" class="date-select-field " role="button" aria-expanded="true">
                                <span data-v-c57906f2="">Period of display</span><span data-v-c57906f2=""
                                    class="dropdown-arrow is-open"></span></div>
                            <div data-v-c57906f2="" class="date-options-dropdown" style="display: none;">
                                <div data-v-c57906f2="" class="preset-ranges"><button data-v-c57906f2=""
                                        class="range-option">Today</button><button data-v-c57906f2=""
                                        class="range-option">Yesterday</button><button data-v-c57906f2=""
                                        class="range-option">Last 7 days</button><button data-v-c57906f2=""
                                        class="range-option">Last 30 days</button><button data-v-c57906f2=""
                                        class="range-option">This month</button><button data-v-c57906f2=""
                                        class="range-option">Last month</button><button data-v-c57906f2=""
                                        class="range-option active">Custom Range</button></div>
                                <!---->
                            </div>
                        </div>
                        <span class="icon-date"></span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Advertising
                                    networks</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span
                                    class="multi-select__placeholder">Languages</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Operation
                                    systems</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span
                                    class="multi-select__placeholder">Browsers</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span
                                    class="multi-select__placeholder">Devices</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Image
                                    sizes</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <label class="checkbox-toggle _with-background">
                        <span class="icon-18 font-20"></span>
                        <span class="mr-auto">Only adult</span>
                        <input type="checkbox" id="adult">
                        <span class="checkbox-toggle-visible"></span>
                    </label>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <div class="filter-section">
                        <div class="multi-select" disabled="false">
                            <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Saved
                                    settings</span></div>
                            <div class="multi-select__dropdown" style="display: none;">
                                <div class="multi-select__search"><input type="text" placeholder="Search"
                                        class="multi-select__search-input"></div>
                                <ul class="multi-select__options">
                                    <li class="">Option 1</li>
                                    <li class="">Option 2</li>
                                    <li class="">Option 3</li>
                                </ul>
                            </div><span class="multi-select__arrow"></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto mb-10">
                    <button class="btn _flex _dark _medium w-100"><span class="icon-save mr-2 font-16"></span>Save
                        settings</button>
                </div>
            </div>
            <div class="reset-btn d-md-none">
                <button class="btn-icon"><span class="icon-clear"></span> <span
                        class="ml-2 d-md-none">Reset</span></button>
            </div>
        </div>
    </div>
</div>