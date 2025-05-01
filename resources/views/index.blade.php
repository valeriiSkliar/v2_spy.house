@extends('layouts.main')

@section('page-content')
<h1>Pages</h1>
<!-- <ul class="menu-demo">
    <li><a href="creatives_push.html">Creatives Push</a></li>
    <li><a href="creatives_inpage.html">Creatives InPage</a></li>
    <li><a href="creatives_fb.html">Creatives Facebook/TikTok</a></li>
    <li><a href="services_1.html">Services 1</a></li>
    <li><a href="services_2.html">Services 2</a></li>
    <li><a href="landings_1.html">Landings 1</a></li>
    <li><a href="landings_2.html">Landings 2</a></li>
    <li><a href="profile_1.html">Profile </a></li>
    <li><a href="profile_change-password.html">Profile - Change password</a></li>
    <li><a href="notifications.html">Notifications</a></li>
    <li><a href="tariffs.html">Tariffs</a></li>
    <li><a href="tariffs_pay.html">Tariffs - Pay</a></li>
    <li><a data-toggle="modal" data-target="#modal-subscription-activated">Subscription activated</a></li>
    <li><a href="finances.html">Finances</a></li>
    <li><a data-toggle="modal" data-target="#modal-contacts">Contacts</a></li>
    <li><a href="blog.html">Blog</a></li>
    <li><a href="blog_single.html">Blog Single</a></li>
    <li><a href="api.html">API</a></li>
</ul> -->

<div style="background: #243035; padding: 10px 10px 5px; border-radius: 10px; margin-bottom: 20px; margin-top: 20px;">
    <div class="row">
        <div class="col-auto mb-2">
            <x-tariff-link>Free</x-tariff-link>
        </div>
        <div class="col-auto mb-2">
            <x-tariff-link type="start">Start</x-tariff-link>
        </div>
        <div class="col-auto mb-2">
            <x-tariff-link type="basic">Basic</x-tariff-link>
        </div>
        <div class="col-auto mb-2">
            <x-tariff-link type="premium">Premium</x-tariff-link>
        </div>
        <div class="col-auto mb-2">
            <x-tariff-link type="enterprise">Enterprise</x-tariff-link>
        </div>
    </div>
</div>

<h2 class="mb-10">Base select</h2>
<div class="section mb-20">
    @include('components.base-select', [
    'selected' => ['value' => 'eng', 'label' => 'Eng', 'order' => ''],
    'options' => [
    ['value' => 'eng', 'label' => 'Eng', 'order' => ''],
    ['value' => 'uk', 'label' => 'Uk', 'order' => ''],
    ['value' => 'esp', 'label' => 'Esp', 'order' => ''],
    ]
    ])
</div>


<h2 class="mb-10">Multi select</h2>
<div class="section mb-20">
    <div class="row">
        <div class="col-12 col-md-6 mb-10">
            <div class="filter-section">
                <div class="multi-select" disabled="false">
                    <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Select OS</span></div>
                    <div class="multi-select__dropdown" style="display: none;">
                        <div class="multi-select__search"><input type="text" placeholder="Search" class="multi-select__search-input"></div>
                        <ul class="multi-select__options">
                            <li class=""><!----> android</li>
                            <li class=""><!----> blackberry</li>
                            <li class=""><!----> bot</li>
                            <li class=""><!----> chromeos</li>
                            <li class=""><!----> ios</li>
                            <li class=""><!----> kindle</li>
                            <li class=""><!----> linux</li>
                            <li class=""><!----> macosx</li>
                            <li class=""><!----> other</li>
                            <li class=""><!----> playstation</li>
                            <li class=""><!----> unknown</li>
                            <li class=""><!----> webos</li>
                            <li class=""><!----> windows</li>
                            <li class=""><!----> windowsphone</li>
                            <li class=""><!----> xbox</li>
                        </ul>
                    </div><span class="multi-select__arrow"></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-10">
            <div class="filter-section">
                <div class="multi-select" disabled="false">
                    <div class="multi-select__tags">
                        <span class="multi-select__tag">webos <button type="button" class="multi-select__remove"> × </button></span>
                        <span class="multi-select__tag">windowsphone <button type="button" class="multi-select__remove"> × </button></span>
                        <span class="multi-select__tag">xbox <button type="button" class="multi-select__remove"> × </button></span>
                    </div>
                    <div class="multi-select__dropdown" style="display: none;">
                        <div class="multi-select__search"><input type="text" placeholder="Search" class="multi-select__search-input"></div>
                        <ul class="multi-select__options">
                            <li class=""><!----> android</li>
                            <li class=""><!----> blackberry</li>
                            <li class=""><!----> bot</li>
                            <li class=""><!----> chromeos</li>
                            <li class=""><!----> ios</li>
                            <li class=""><!----> kindle</li>
                            <li class=""><!----> linux</li>
                            <li class=""><!----> macosx</li>
                            <li class=""><!----> other</li>
                            <li class=""><!----> playstation</li>
                            <li class=""><!----> unknown</li>
                            <li class="selected"><!----> webos</li>
                            <li class=""><!----> windows</li>
                            <li class="selected"><!----> windowsphone</li>
                            <li class="selected"><!----> xbox</li>
                        </ul>
                    </div><span class="multi-select__arrow"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection