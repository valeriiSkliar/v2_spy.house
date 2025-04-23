@extends('layouts.api')

@section('api-content')
<div class="mb-15">
    <x-message type="orange" icon="i">
        To open access to our API, contact our support service via Telegram at the link: <a href="#" class="link _not-line">@spy.house_support</a>
    </x-message>
</div>

<div class="section mb-20 pb-3">
    <h2>Api key</h2>
    <p class="mb-20">API Key is your secret key for working with API spy.house</p>
    <div class="row _offset20">
        <div class="col-12 col-md-auto flex-grow-1 mb-15">
            <x-form-copy value="https://spy.house/rules/5675tghjh/345467dsfgh/43567" />
        </div>
        <div class="col-12 col-md-auto mb-15">
            <button class="btn _flex _green _big min-200 w-100">Generate</button>
        </div>
    </div>
    <div class="row justify-content-between pt-2">
        <div class="col-12 col-lg-auto">
            <x-message type="dark" icon="warning" class="mb-15">
                Available requests today <strong class="txt-green">100</strong> out of 100
            </x-message>
            <x-message type="dark" icon="warning" class="mb-15">
                Available requests today <strong class="txt-red">0</strong> out of 100
            </x-message>
        </div>
        <div class="col-12 col-lg-auto">
            <x-message type="gray" icon="clock" class="mb-15">
                The key has been used for the last time - <strong>28.10.24</strong>
            </x-message>
        </div>
    </div>
    <div class="mb-15">
        <x-message type="red" icon="warning">
            Исчерпано количество запросов. Чтобы получить больше, повысте <a href="#" class="link">тарифный план</a>
        </x-message>
    </div>
</div>

<div class="section mb-20">
    <h2>Url for request</h2>
    <x-form-copy value="https://spy.house/rules/5675tghjh/345467dsfgh/43567" green />
</div>

<div class="mb-20">
    <x-api-tabs tabsGroup="flows" :tabs="[
            ['id' => 'request', 'name' => 'Request parameters', 'active' => true],
            ['id' => 'response', 'name' => 'Response parameters', 'active' => false],
            ['id' => 'php', 'name' => 'PHP example', 'active' => false],
        ]" />

    <div class="tubs-content">
        <x-tab-content id="request" group="flows" :active="true">
            <div class="c-table">
                <div class="inner">
                    <table class="table thead-transparent">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Data type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>api_key</td>
                                <td>String</td>
                                <td>Yes</td>
                                <td>Unique API key for request authentication.</td>
                            </tr>
                            <tr>
                                <td>date_ranges</td>
                                <td>String</td>
                                <td>No</td>
                                <td>Date range for filtering in DD.MM.YYYY - DD.MM.YYYY format. E.g., 01.01.2023 - 31.12.2023.</td>
                            </tr>
                            <tr>
                                <td>status</td>
                                <td>String</td>
                                <td>No</td>
                                <td>Flow status filter: active, pause, deleted.</td>
                            </tr>
                            <tr>
                                <td>search</td>
                                <td>String</td>
                                <td>No</td>
                                <td>String for searching by flow name or fields.</td>
                            </tr>
                            <tr>
                                <td>per_page</td>
                                <td>Number</td>
                                <td>No</td>
                                <td>Number of records per page (10 to 200). Default: 10.</td>
                            </tr>
                            <tr>
                                <td>page</td>
                                <td>Number</td>
                                <td>No</td>
                                <td>Page number, starting from 1. Default: 1.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-tab-content>

        <x-tab-content id="response" group="flows">
            <div class="c-table">
                <div class="inner">
                    <table class="table thead-transparent">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Data type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>status</td>
                                <td>String</td>
                                <td>Field indicating successful request execution ("success") or error ("error").</td>
                            </tr>
                            <tr>
                                <td>msg</td>
                                <td>String</td>
                                <td>Error message or additional request information.</td>
                            </tr>
                            <tr>
                                <td>code</td>
                                <td>Number</td>
                                <td>Response code, e.g., 200 for a successful request.</td>
                            </tr>
                            <tr>
                                <td>total</td>
                                <td>Number</td>
                                <td>Total number of flows matching the request.</td>
                            </tr>
                            <tr>
                                <td>per_page</td>
                                <td>Number</td>
                                <td>Number of records per page.</td>
                            </tr>
                            <tr>
                                <td>page</td>
                                <td>Number</td>
                                <td>Current page number in pagination.</td>
                            </tr>
                            <tr>
                                <td>data</td>
                                <td>Array</td>
                                <td>Array of flow data matching the request.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-tab-content>

        <x-tab-content id="php" group="flows">
            <div class="code">
                <code><span style="color: #000000">
                        <span style="color: #0000BB">&lt;?php<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">function&nbsp;</span><span style="color: #0000BB">api_request</span><span style="color: #007700">(</span><span style="color: #0000BB">$data&nbsp;</span><span style="color: #007700">=&nbsp;[])<br>&nbsp;&nbsp;&nbsp;&nbsp;{<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$ch&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">curl_init</span><span style="color: #007700">(</span><span style="color: #DD0000">'https://cloaking.house/api/flows'</span><span style="color: #007700">);<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_setopt</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURLOPT_RETURNTRANSFER</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">TRUE</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_setopt</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURLOPT_CUSTOMREQUEST</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">'POST'</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_setopt</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURLOPT_SSL_VERIFYPEER</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">FALSE</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_setopt</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURLOPT_IPRESOLVE</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURL_IPRESOLVE_V4</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_setopt</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">CURLOPT_POSTFIELDS</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">http_build_query</span><span style="color: #007700">(</span><span style="color: #0000BB">$data</span><span style="color: #007700">));<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$body&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">curl_exec</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$info&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">curl_getinfo</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">);<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">curl_close</span><span style="color: #007700">(</span><span style="color: #0000BB">$ch</span><span style="color: #007700">);<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if&nbsp;(&nbsp;!&nbsp;empty(</span><span style="color: #0000BB">$info</span><span style="color: #007700">[</span><span style="color: #DD0000">'http_code'</span><span style="color: #007700">])&nbsp;&amp;&amp;&nbsp;</span><span style="color: #0000BB">$info</span><span style="color: #007700">[</span><span style="color: #DD0000">'http_code'</span><span style="color: #007700">]&nbsp;==&nbsp;</span><span style="color: #0000BB">200</span><span style="color: #007700">)&nbsp;{<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return&nbsp;</span><span style="color: #0000BB">json_decode</span><span style="color: #007700">(</span><span style="color: #0000BB">$body</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">TRUE</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>&nbsp;&nbsp;&nbsp;&nbsp;}<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$body_request&nbsp;</span><span style="color: #007700">=&nbsp;[<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'api_key'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'YOUR_API_KEY'</span><span style="color: #007700">,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'date_ranges'&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'01.01.2023&nbsp;-&nbsp;31.12.2023'</span><span style="color: #007700">,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'status'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'active'</span><span style="color: #007700">,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'search'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'84b593db13ccd6acf79ec7287d5da586'</span><span style="color: #007700">,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'per_page'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">10</span><span style="color: #007700">,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'page'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">1<br>&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #007700">];<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$api_request&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">api_request</span><span style="color: #007700">(</span><span style="color: #0000BB">$body_request</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;if&nbsp;(&nbsp;!&nbsp;empty(</span><span style="color: #0000BB">$api_request</span><span style="color: #007700">[</span><span style="color: #DD0000">'status'</span><span style="color: #007700">]))&nbsp;{<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo&nbsp;</span><span style="color: #DD0000">'&lt;pre&gt;'</span><span style="color: #007700">;<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">print_r</span><span style="color: #007700">(</span><span style="color: #0000BB">$api_request</span><span style="color: #007700">);<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo&nbsp;</span><span style="color: #DD0000">'&lt;/pre&gt;'</span><span style="color: #007700">;<br>&nbsp;&nbsp;&nbsp;&nbsp;}<br></span>
                    </span>
                </code>
            </div>
        </x-tab-content>
    </div>
</div>

<style>
    .api-tabs {
        display: flex;
        margin-bottom: 15px;
        border-radius: 5px;
        overflow: hidden;
    }

    .api-tab {
        padding: 12px 20px;
        background-color: #f0f2f5;
        color: #6c7a89;
        text-decoration: none;
        text-align: center;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        flex: 1;
    }

    .api-tab:hover {
        background-color: #e4e8ee;
        color: #465361;
    }

    .api-tab.active {
        background-color: #2ecc71;
        color: #ffffff;
    }

    .api-tab:first-child {
        border-top-left-radius: 5px;
        border-bottom-left-radius: 5px;
    }

    .api-tab:last-child {
        border-top-right-radius: 5px;
        border-bottom-right-radius: 5px;
    }
</style>
@endsection