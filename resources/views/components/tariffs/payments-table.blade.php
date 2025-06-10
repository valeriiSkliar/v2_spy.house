<h2>Мои платежи</h2>
<div id="payments-container" data-payments-ajax-url="{{ route('api.tariffs.payments') }}">
    <div class="c-table">
        <div class="inner">
            <table class="table thead-transparent no-wrap-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Название</th>
                        <th>Тип</th>
                        <th>Метод оплаты</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody id="payments-list">
                    <x-tariffs.payments-list :payments="$payments" />
                </tbody>
            </table>
        </div>
    </div>
</div>