<?php

namespace App\Controllers\Admin;

use App\Services\AuthService;
use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class DashboardController extends Controller
{
    public function __construct(protected AuthService $auth)
    {
    }

    /**
     * Show the authenticated admin dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();

        return $this->view([
            'user' => $user,
            'appName' => 'SyntaxCore',
        ]);
    }
}
