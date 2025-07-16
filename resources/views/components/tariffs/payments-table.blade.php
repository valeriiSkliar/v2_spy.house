<h2>{{ __('tariffs.payments_table.title') }}</h2>
<div id="payments-container" data-payments-ajax-url="{{ route('api.tariffs.payments') }}">
    <div class="c-table">
        <div class="inner">
            <table class="table thead-transparent no-wrap-table">
                <thead>
                    <tr>
                        <th>{{ __('tariffs.payments_table.columns.date') }}</th>
                        <th>{{ __('tariffs.payments_table.columns.name') }}</th>
                        <th>{{ __('finances.history_table.transaction_number') }}</th>
                        <th>{{ __('tariffs.payments_table.columns.type') }}</th>
                        <th>{{ __('tariffs.payments_table.columns.payment_method') }}</th>
                        <th>{{ __('tariffs.payments_table.columns.amount') }}</th>
                        <th>{{ __('tariffs.payments_table.columns.status') }}</th>
                    </tr>
                </thead>
                <tbody id="payments-list">
                    <x-tariffs.payments-list :payments="$payments" />
                </tbody>
            </table>
        </div>
    </div>
</div>