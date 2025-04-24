<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogController extends Controller
{


    /**
     * Моковые данные для статей
     */
    private function getArticles()
    {
        return [
            [
                'id' => 1,
                'title' => 'How the Arbitration Business Has Changed in 2021 and Forecasts for 2022',
                'slug' => 'arbitration-business-2021-2022',
                'is_new' => true,
                'image' => 'https://blog.spy.house/wp-content/uploads/2023/07/PH_blog_15.png',
                'excerpt' => 'CPA market leaders shared what changes occurred in the arbitrage business in 2021, how they carried out automation in their teams',
                'content' => '<p>Главной задачей арбитражника при работе с betting офферами является умение следить за всеми основными событиями и трендами в мире спорта.  Одним из таких событий является сезон UEFA 2022/2023, который привлекает внимание миллионов футбольных болельщиков со всего мира. Беттинг офферы, связанные с футболом, предоставляют отличную возможность заработка для арбитражников, а push уведомления являются хорошим источником качественного трафика.</p>
                <p> В этой статье мы расскажем, как продвигать беттинг офферы для UEFA 2022/2023 в <a href="#">Push.House</a> и разберем основные моменты по ГЕО, целевой аудитории и креативам. </p>
                <h2>Введение</h2>
                <p>Итак, поскольку финал Лиги Чемпионов не за горами (10 июня 2023 года в Стамбуле состоится финальный матч между Манчестер Сити и Интер Милан), самое время начать подготовку рекламных кампаний.</p>
                <h3>Целевая Аудитория и ГЕО:</h3>
                <p><strong>Статистика от нашего аналитического отдела по показам и кликам за последний период: </strong></p>
                <blockquote>They allow you to earn more Elons with smaller amounts and thus contribute to faster battery charging!</blockquote>
                <ul>
                    <li>With the new max bid you can earn 1 Elon with just $8,000!</li>
                    <li>Stay tuned, we\'ll be back with the kids very soon!</li>
                    <li>Increase your chances of launching a Tesla! 🚀</li>
                </ul>
                <p>В конечном итоге, применение A/B-тестирования является лучшим способом найти рабочую связку. Тестирование различных вариантов заголовков, описаний и изображений поможет определить наиболее эффективные комбинации и улучшить результаты кампаний.</p>
                <h4>Lorem ipsum dolor sit amet, consectetur adipisicing.</h4>
                <ol>
                    <li>With the new max bid you can earn 1 Elon with just $8,000!</li>
                    <li>Stay tuned, we\'ll be back with the kids very soon!</li>
                    <li>Increase your chances of launching a Tesla! 🚀</li>
                </ol>',
                'date' => '11.05.25',
                'views' => 1,
                'rating' => 4.5,
                'user_rating' => 1,
                'category' => [
                    'id' => 1,
                    'name' => 'Push traffic',
                    'slug' => 'push-traffic',
                    'color' => '#CD4F51'
                ],
                'table_of_contents' => [
                    ['title' => 'Tired of seeing your battery in red?', 'link' => '#'],
                    ['title' => 'Want to be proud of another Elon?', 'link' => '#'],
                    ['title' => 'Time to pump it up!', 'link' => '#'],
                    ['title' => 'We\'re releasing chargers - updated call rates', 'link' => '#'],
                    ['title' => 'AdCombo offers', 'link' => '#']
                ],
                'comments' => [
                    [
                        'id' => 1,
                        'author' => 'Guillermo Emmerich',
                        'date' => '02.01.2025',
                        'content' => 'Illo praesentium qui labore suscipit hic laborum maiores. Eveniet sunt accusantium rerum totam et qui. Molestias dolores velit dolores.',
                        'replies' => []
                    ],
                    [
                        'id' => 2,
                        'author' => 'Gerry Davis',
                        'date' => '02.01.2025',
                        'content' => 'Et neque dolorem nihil earum aut. Voluptas impedit vitae sunt repudiandae. Dolor sint illo sed quaerat totam.',
                        'replies' => [
                            [
                                'id' => 3,
                                'author' => 'Tania Blick',
                                'date' => '02.01.2025',
                                'content' => 'Voluptas et dolores quis aut veniam. Alias quae quae ullam ratione. Sint qui earum culpa minus accusantium nam aperiam. Fuga architecto rem veritatis deleniti molestias eligendi harum rerum.'
                            ],
                            [
                                'id' => 4,
                                'author' => 'Ms. Vanessa Botsford',
                                'date' => '02.01.2025',
                                'content' => 'Impedit error autem consectetur necessitatibus voluptatem. Rerum quam et et in.'
                            ],
                            [
                                'id' => 5,
                                'author' => 'Julia Wintheiser',
                                'date' => '02.01.2025',
                                'content' => 'Aut qui vero eaque ut tempora consectetur recusandae aperiam. Repellendus iure omnis occaecati sit sint. Quaerat voluptatem inventore et adipisci dolores. Beatae et tempore delectus ut maiores.'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 2,
                'title' => 'Антикейс: как я потерял $650 в P2E игре в 1 клик',
                'slug' => 'anticase-p2e-game',
                'is_new' => false,
                'image' => 'https://blog.spy.house/wp-content/uploads/2023/06/PH_blog_microbidding_02.png',
                'excerpt' => 'История о том, как можно потерять деньги в Play-to-Earn игре из-за одной ошибки',
                'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod, nisl vel tincidunt luctus, nisl nisl aliquam magna, nec aliquam nisl nisl nec nisl.</p>',
                'date' => '10.05.25',
                'views' => 12356,
                'rating' => 4.5,
                'user_rating' => 3,
                'category' => [
                    'id' => 2,
                    'name' => 'Арбитражнику',
                    'slug' => 'arbitrazhniku',
                    'color' => '#694fcd'
                ],
                'comments' => []
            ],
            [
                'id' => 3,
                'title' => 'Запуск push-трафика на беттинг офферы в сезон UEFA 2022/2023',
                'slug' => 'push-traffic-betting-uefa',
                'is_new' => true,
                'image' => 'https://blog.spy.house/wp-content/uploads/2023/06/PH_blog.png',
                'excerpt' => 'Все о запуске push-трафика на беттинг офферы в период UEFA',
                'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eveniet exercitationem harum laborum nihil placeat. Dolore, ratione vero! Id laborum, magnam!</p>',
                'date' => '09.05.25',
                'views' => 543,
                'rating' => 4.2,
                'user_rating' => 2,
                'category' => [
                    'id' => 3,
                    'name' => 'Полезное',
                    'slug' => 'poleznoe',
                    'color' => '#33b485'
                ],
                'comments' => []
            ]
        ];
    }

    /**
     * Моковые данные для категорий
     */
    private function getCategories()
    {
        return [
            [
                'id' => 1,
                'name' => 'Push traffic',
                'slug' => 'push-traffic',
                'color' => '#CD4F51',
                'count' => 12
            ],
            [
                'id' => 2,
                'name' => 'Teaser networks',
                'slug' => 'teaser-networks',
                'color' => '#4F98CD',
                'count' => 66
            ],
            [
                'id' => 3,
                'name' => 'CPA networks',
                'slug' => 'cpa-networks',
                'color' => '#33b485',
                'count' => 23
            ],
            [
                'id' => 4,
                'name' => 'Social networks',
                'slug' => 'social-networks',
                'color' => '#694fcd',
                'count' => 56
            ]
        ];
    }

    /**
     * Отображение списка статей блога
     */
    public function index(Request $request)
    {
        $articles = $this->getArticles();
        $categories = $this->getCategories();

        // Фильтрация по категории, если передан параметр
        if ($request->has('category')) {
            $categorySlug = $request->category;
            $category = collect($categories)->firstWhere('slug', $categorySlug);

            if ($category) {
                $articles = collect($articles)
                    ->filter(function ($article) use ($category) {
                        return $article['category']['id'] === $category['id'];
                    })
                    ->all();
            }
        }

        // Пагинация
        $perPage = 6;
        $currentPage = $request->get('page', 1);
        $totalArticles = count($articles);
        $totalPages = ceil($totalArticles / $perPage);

        $articles = collect($articles)
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->all();

        return view('blog.index', [
            'articles' => $articles,
            'categories' => $categories,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages
        ]);
    }

    /**
     * Отображение отдельной статьи блога
     */
    public function show($slug)
    {
        $articles = $this->getArticles();
        $article = collect($articles)->firstWhere('slug', $slug);

        if (!$article) {
            abort(404);
        }

        $relatedArticles = collect($articles)
            ->filter(function ($item) use ($article) {
                return $item['id'] !== $article['id'];
            })
            ->all();

        $breadcrumbs = [
            ['title' => 'Blog', 'url' => route('blog.index')],
            ['title' => 'Arbitrage', 'url' => '#'],
            ['title' => 'Social networks', 'url' => '#'],
            ['title' => $article['title']]
        ];

        return view('blog.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'breadcrumbs' => $breadcrumbs,
            'commentsPages' => 1,
            'currentPage' => 1
        ]);
    }

    /**
     * Подготовка формы ответа на комментарий
     */
    public function reply($slug, $comment_id)
    {
        $article = collect($this->getArticles())->firstWhere('slug', $slug);

        if (!$article) {
            abort(404);
        }

        // Найти комментарий по ID
        $comment = null;
        foreach ($article['comments'] as $articleComment) {
            if ($articleComment['id'] == $comment_id) {
                $comment = $articleComment;
                break;
            }

            if (!empty($articleComment['replies'])) {
                foreach ($articleComment['replies'] as $reply) {
                    if ($reply['id'] == $comment_id) {
                        $comment = $reply;
                        break 2;
                    }
                }
            }
        }

        if (!$comment) {
            abort(404);
        }

        return redirect()->route('blog.show', $slug)->with('reply_to', [
            'id' => $comment_id,
            'author' => $comment['author']
        ]);
    }

    /**
     * Сохранение нового комментария
     */
    public function storeComment(Request $request, $slug)
    {
        $request->validate([
            'content' => 'required|min:5'
        ]);

        // В реальном приложении здесь был бы код для сохранения комментария в БД

        return redirect()->route('blog.show', $slug)->with('success', 'Comment added successfully');
    }

    /**
     * Сохранение ответа на комментарий
     */
    public function storeReply(Request $request, $slug)
    {
        $request->validate([
            'content' => 'required|min:5',
            'parent_id' => 'required|numeric'
        ]);

        // В реальном приложении здесь был бы код для сохранения ответа в БД

        return redirect()->route('blog.show', $slug)->with('success', 'Reply added successfully');
    }

    public function rateArticle(Request $request, $slug)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5'
        ]);

        $article = collect($this->getArticles())->firstWhere('slug', $slug);

        if (!$article) {
            return response()->json(['success' => false, 'message' => 'Article not found'], 404);
        }

        // В реальном приложении здесь будет код для сохранения рейтинга в БД

        return response()->json([
            'success' => true,
            'rating' => $request->rating,
            'message' => 'Rating saved successfully'
        ]);
    }
}
