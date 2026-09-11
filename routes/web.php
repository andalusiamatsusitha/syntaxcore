<?php

use App\Controllers\Web\CmsPublicController;
use App\Controllers\Web\HomeController;
use Core\Routing\Router;

/** @var Router $router */

// Public Landing / Home
$router->get('/', [HomeController::class, 'index']);

// System Health Check
$router->get('/health', function () {
    return [
        'status' => 'healthy',
        'framework' => 'SyntaxCore',
        'timestamp' => date('c'),
    ];
});

// CMS Public Engine Routes
$router->get('/berita', [CmsPublicController::class, 'newsIndex']);
$router->get('/berita/kategori/{slug}', [CmsPublicController::class, 'newsCategory']);
$router->get('/berita/tag/{slug}', [CmsPublicController::class, 'newsTag']);
$router->get('/berita/{slug}', [CmsPublicController::class, 'newsSingle']);
$router->get('/baca-berita', [CmsPublicController::class, 'newsSingleReader']);

// Submit Comment on News (Wrapped in CSRF protection)
$router->group(['middleware' => 'csrf'], function (Router $router) {
    $router->post('/news/{id}/comments', [CmsPublicController::class, 'submitComment']);
});

// Dynamic Catch-All Page Resolver (e.g. /tentang-kami, /kontak, dll.)
$router->get('/{slug}', [CmsPublicController::class, 'page']);
