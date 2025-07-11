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
        // Получаем текущую локаль пользователя
        $locale = app()->getLocale();

        // Загружаем статьи блога с учетом текущей локали
        $blogPosts = BlogPost::where('is_published', true)
            // Фильтруем статьи, у которых есть заголовок на текущей локали
            ->whereRaw("JSON_EXTRACT(title, '$.\"{$locale}\"') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(title, '$.\"{$locale}\"') != ''")
            // Фильтруем статьи, у которых есть контент на текущей локали
            ->whereRaw("JSON_EXTRACT(content, '$.\"{$locale}\"') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(content, '$.\"{$locale}\"') != ''")
            ->with(['author', 'categories'])
            ->withCount('comments') // Загружаем количество комментариев для избежания N+1
            ->orderBy('created_at', 'desc')
            ->take(4) // Берем только 4 последние статьи для слайдера
            ->get();

        // Передаем статьи в view
        $view->with('blogPosts', $blogPosts);
    }
}
