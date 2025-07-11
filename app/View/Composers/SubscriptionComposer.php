<?php

namespace App\View\Composers;

use App\Finance\Models\Subscription;
use Illuminate\View\View;

class SubscriptionComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        // Загружаем только платные активные подписки, исключаем Free план
        $subscriptions = Subscription::where('status', 'active')
            ->where('amount', '>', 0)
            ->orderBy('amount', 'asc')
            ->get();

        // Передаем подписки в view
        $view->with('subscriptions', $subscriptions);
    }
}
