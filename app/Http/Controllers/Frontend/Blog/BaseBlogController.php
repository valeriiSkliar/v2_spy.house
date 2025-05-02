<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\FrontendController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BaseBlogController extends FrontendController
{
    use AuthorizesRequests;
    protected $indexView = 'blog.index';
    protected $showView = 'blog.show';
}
