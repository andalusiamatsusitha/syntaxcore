<?php

namespace App\Controllers\Web;

use App\Models\News;
use App\Models\Page;
use App\Models\PublicMenu;
use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $page = Page::findBySlug('beranda');
        $menus = PublicMenu::tree(true);
        $recentNews = News::getPublishedNews(limit: 3);

        return $this->view([
            'appName' => 'SyntaxCore',
            'version' => '1.0.0',
            'phpVersion' => PHP_VERSION,
            'page' => $page,
            'menus' => $menus,
            'recentNews' => $recentNews,
            'activeMenu' => '/',
        ]);
    }
}
