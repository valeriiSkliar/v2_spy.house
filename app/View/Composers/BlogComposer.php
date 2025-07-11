<?php

namespace App\View\Composers;

use App\Models\Frontend\Blog\BlogPost;
use Illuminate\View\View;

class BlogComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        // Загружаем последние опубликованные статьи блога с необходимыми связями
        $blogPosts = BlogPost::where('is_published', true)
            ->with(['author', 'categories'])
            ->withCount('comments') // Загружаем количество комментариев для избежания N+1
            ->orderBy('created_at', 'desc')
            ->take(4) // Берем только 4 последние статьи для слайдера
            ->get();

        // Передаем статьи в view
        $view->with('blogPosts', $blogPosts);
    }
}
