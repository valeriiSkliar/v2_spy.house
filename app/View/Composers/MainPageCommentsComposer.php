<?php

namespace App\View\Composers;

use App\Models\Frontend\MainPageComments;
use Illuminate\View\View;

class MainPageCommentsComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        // Загружаем только активные отзывы, отсортированные по порядку отображения
        $reviews = MainPageComments::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Передаем отзывы в view
        $view->with('reviews', $reviews);
    }
}
