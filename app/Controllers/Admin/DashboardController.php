<?php

namespace App\Controllers\Admin;

use App\Services\AuthService;
use App\Services\MenuService;
use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AuthService $auth,
        protected MenuService $menuService = new MenuService()
    ) {
    }

    /**
     * Show the authenticated admin dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        $menus = $this->menuService->getMenusForUser($user);

        return $this->view([
            'user' => $user,
            'role' => $user?->role(),
            'roleName' => $user?->role()?->name ?? 'Administrator',
            'roleSlug' => $user?->roleSlug() ?? 'admin',
            'appName' => 'SyntaxCore',
            'menus' => $menus,
        ]);
    }

    /**
     * Get user-level menus in JSON format.
     */
    public function menus(Request $request): Response
    {
        $user = $this->auth->user();
        $menus = $this->menuService->getMenusForUser($user);

        return $this->json([
            'status' => 'success',
            'user' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'role' => $user?->roleSlug() ?? 'admin',
                'role_name' => $user?->role()?->name ?? 'Administrator',
                'level' => $user?->roleLevel() ?? 1,
            ],
            'menus' => $menus,
        ]);
    }
}
