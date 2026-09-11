<?php

namespace App\Services;

use App\Models\User;

class MenuService
{
    /**
     * Definisi master menu aplikasi beserta batas minimal level pengguna.
     */
    protected array $menuDefinitions = [
        [
            'id' => 'dashboard',
            'title' => 'Dashboard Overview',
            'icon' => 'fa-solid fa-gauge-high',
            'action' => 'open_dashboard',
            'min_level' => 'user', // user, admin, superadmin
        ],
        [
            'id' => 'profile',
            'title' => 'Profil Saya',
            'icon' => 'fa-solid fa-user-gear',
            'action' => 'open_profile',
            'min_level' => 'user',
        ],
        [
            'id' => 'users',
            'title' => 'Manajemen Pengguna',
            'icon' => 'fa-solid fa-users',
            'action' => 'open_users',
            'min_level' => 'admin',
        ],
        [
            'id' => 'reports',
            'title' => 'Laporan Aktivitas',
            'icon' => 'fa-solid fa-chart-line',
            'action' => 'open_reports',
            'min_level' => 'admin',
        ],
        [
            'id' => 'database',
            'title' => 'Database Explorer',
            'icon' => 'fa-solid fa-database',
            'action' => 'open_database',
            'min_level' => 'superadmin',
        ],
        [
            'id' => 'settings',
            'title' => 'Pengaturan Sistem',
            'icon' => 'fa-solid fa-gears',
            'action' => 'open_settings',
            'min_level' => 'superadmin',
        ],
    ];

    /**
     * Ambil daftar menu yang diizinkan untuk user tertentu berdasarkan level/role.
     */
    public function getMenusForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        // Bobot level/role hierarki
        $roleHierarchy = [
            'user' => 1,
            'admin' => 2,
            'superadmin' => 3,
        ];

        // Ambil level dari user, default ke 'admin' jika belum diset di tabel
        $userRole = strtolower((string) ($user->role ?? $user->level ?? 'admin'));
        $userLevelScore = $roleHierarchy[$userRole] ?? 1;

        $allowedMenus = [];
        foreach ($this->menuDefinitions as $menu) {
            $requiredScore = $roleHierarchy[$menu['min_level']] ?? 1;

            if ($userLevelScore >= $requiredScore) {
                $allowedMenus[] = [
                    'id' => $menu['id'],
                    'title' => $menu['title'],
                    'icon' => $menu['icon'],
                    'action' => $menu['action'],
                    'min_level' => $menu['min_level'],
                ];
            }
        }

        return $allowedMenus;
    }
}
