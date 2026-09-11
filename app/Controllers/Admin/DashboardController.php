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

    /**
     * Endpoint Manajemen Pengguna (Accessible by: admin, superadmin)
     */
    public function users(Request $request): Response
    {
        $users = \App\Models\User::all();
        $userList = array_map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roleSlug(),
                'role_name' => $u->role()?->name ?? 'User',
                'level' => $u->roleLevel(),
            ];
        }, $users);

        return $this->json([
            'status' => 'success',
            'module' => 'Manajemen Pengguna',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'total' => count($userList),
            'users' => $userList,
        ]);
    }

    /**
     * Endpoint Laporan Aktivitas (Accessible by: admin, superadmin)
     */
    public function reports(Request $request): Response
    {
        return $this->json([
            'status' => 'success',
            'module' => 'Laporan Aktivitas',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => 'Ringkasan aktivitas sistem dan log transaksi pengguna.',
        ]);
    }

    /**
     * Endpoint Database Explorer (Accessible ONLY by: superadmin)
     */
    public function database(Request $request): Response
    {
        return $this->json([
            'status' => 'success',
            'module' => 'Database Explorer',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'server' => 'MySQL 8.0',
            'tables' => ['roles', 'users', 'menus', 'role_menu'],
        ]);
    }

    /**
     * Endpoint Pengaturan Sistem (Accessible ONLY by: superadmin)
     */
    public function settings(Request $request): Response
    {
        return $this->json([
            'status' => 'success',
            'module' => 'Pengaturan Sistem',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'app_env' => 'local',
            'maintenance_mode' => false,
        ]);
    }
}
