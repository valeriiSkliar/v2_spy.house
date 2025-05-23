<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\FrontendController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BaseBlogController extends FrontendController
{
    use AuthorizesRequests;

    protected $indexView = 'pages.blog.index';

    protected $showView = 'pages.blog.show';
}
