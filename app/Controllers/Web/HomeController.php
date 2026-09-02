<?php

namespace App\Controllers\Web;

use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('web.index', [
            'appName' => 'SyntaxCore',
            'version' => '1.0.0',
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
