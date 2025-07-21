<section class="blog-home">
    <div class="container">
        <div class="row align-items-end _offset30">
            <div class="col-12 col-md-8 pb-md-2">
                <div class="title-label" data-aos-delay="200" data-aos="fade-up">{{ __('main_page.blog') }}</div>
                <h2 class="title" data-aos-delay="200" data-aos="fade-up">{{ __('main_page.blog_blok.title') }}</h2>
            </div>
            <div class="col-12 col-md-4 d-none d-md-flex justify-content-end mb-30" data-aos-delay="200"
                data-aos="fade-up">
                <a href="{{ route('blog.index') }}" class="btn _flex _border-green _large min-170">{{
                    __('main_page.blog_blok.go_to_blog') }} <span
                        class="icon-arrow-up-right ml-2 font-24 pr-0"></span></a>
            </div>
        </div>
        <div class="blog-home__list" data-aos-delay="200" data-aos="fade-up">
            <div class="blog-home-slider">
                @if(isset($blogPosts) && $blogPosts->count() > 0)
                @foreach($blogPosts as $post)
                <div class="article">
                    <a href="{{ route('blog.show', $post->slug) }}" class="article__thumb thumb">
                        @if($post->featured_image)
                        <img src="{{ $post->featured_image }}"
                            alt="{{ $post->getTranslation('title', app()->getLocale()) }}">
                        @else
                        <img src="https://via.placeholder.com/400x250?text=No+Image"
                            alt="{{ $post->getTranslation('title', app()->getLocale()) }}">
                        @endif
                    </a>
                    <div class="article__info">
                        <div class="article-info">
                            <div class="article-info__item icon-date">{{ $post->created_at->format('d.m.y / H:i') }}
                            </div>
                            <a href="{{ route('blog.show', $post->slug) }}#comments"
                                class="article-info__item icon-comment1">{{ $post->comments_count }}</a>
                            <div class="article-info__item icon-view">{{ number_format($post->views_count) }}</div>
                            @if($post->average_rating)
                            <div class="article-info__item icon-rating">{{ number_format($post->average_rating, 1) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('blog.show', $post->slug) }}" class="article__title">{{
                        $post->getTranslation('title', app()->getLocale()) }}</a>
                    <div class="article__desc">{{ Str::limit($post->getTranslation('summary', app()->getLocale()) ?:
                        $post->getTranslation('title', app()->getLocale()), 200) }}</div>
                    <div class="article__bottom">
                        <div class="article__cat">
                            <div class="cat-links">
                                @foreach($post->categories->take(2) as $category)
                                <a href="{{ route('blog.category', $category->slug) }}"
                                    style="color:{{ $loop->first ? '#694FCD' : '#4F98CD' }};">
                                    {{ $category->getTranslation('name', app()->getLocale()) }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="article__link">
                            <a href="{{ route('blog.show', $post->slug) }}" class="btn _flex _green _medium">Read <span
                                    class="icon-next font-16 ml-2"></span></a>
                        </div>
                    </div>
                </div>
                @endforeach
                @else
                {{-- Fallback if articles are not loaded --}}
                <div class="article">
                    <a href="{{ route('blog.index') }}" class="article__thumb thumb">
                        <img src="https://via.placeholder.com/400x250?text=Blog+Post" alt="Blog Post">
                    </a>
                    <div class="article__info">
                        <div class="article-info">
                            <div class="article-info__item icon-date">{{ date('d.m.y / H:i') }}</div>
                            <a href="#" class="article-info__item icon-comment1">0</a>
                            <div class="article-info__item icon-view">0</div>
                            <div class="article-info__item icon-rating">5.0</div>
                        </div>
                    </div>
                    <a href="{{ route('blog.index') }}" class="article__title">Welcome to Our Blog</a>
                    <div class="article__desc">Discover the latest insights, cases, and strategies in the world of
                        affiliate marketing and digital advertising.</div>
                    <div class="article__bottom">
                        <div class="article__cat">
                            <div class="cat-links">
                                <a href="#" style="color:#694FCD;">Arbitrage</a>
                                <a href="#" style="color:#4F98CD;">Guide</a>
                            </div>
                        </div>
                        <div class="article__link">
                            <a href="{{ route('blog.index') }}" class="btn _flex _green _medium">Read <span
                                    class="icon-next font-16 ml-2"></span></a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="d-md-none text-center pt-5" data-aos-delay="200" data-aos="fade-up">
            <a href="{{ route('blog.index') }}" class="btn _flex _border-green _large min-170">Go to blog <span
                    class="icon-arrow-up-right ml-2 font-24 pr-0"></span></a>
        </div>
    </div>
</section>