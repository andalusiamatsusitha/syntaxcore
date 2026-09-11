<?php

/**
 * SyntaxCore Database Seeder
 * Run via CLI: php database/seed.php
 * 
 * Supports environment variables:
 * - SEED_ADMIN_EMAIL: Administrator email address
 * - SEED_ADMIN_PASSWORD: Administrator password
 * - SEED_ADMIN_NAME: Administrator display name
 */

$baseDir = dirname(__DIR__);

if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

$app = require_once $baseDir . '/bootstrap/app.php';

use App\Models\Role;
use App\Models\User;

echo "--- SyntaxCore Database Seeder ---\n";

try {
    // 1. Seed Roles
    $defaultRoles = [
        [
            'name' => 'Super Administrator',
            'slug' => 'superadmin',
            'level' => 3,
            'description' => 'Akses penuh ke semua modul sistem dan konfigurasi inti',
        ],
        [
            'name' => 'Administrator',
            'slug' => 'admin',
            'level' => 2,
            'description' => 'Akses manajerial pengelolaan pengguna dan operasional',
        ],
        [
            'name' => 'Regular User',
            'slug' => 'user',
            'level' => 1,
            'description' => 'Akses standar pengguna',
        ],
    ];

    $rolesMap = [];
    foreach ($defaultRoles as $roleData) {
        $role = Role::findBySlug($roleData['slug']);
        if (!$role) {
            $role = Role::create([
                'name' => $roleData['name'],
                'slug' => $roleData['slug'],
                'level' => $roleData['level'],
                'description' => $roleData['description'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "[SUCCESS] Role seeded: {$roleData['name']} ({$roleData['slug']})\n";
        } else {
            echo "[INFO] Role '{$roleData['slug']}' already exists.\n";
        }
        $rolesMap[$roleData['slug']] = $role;
    }

    // 2. Seed Default Administrator User
    $email = getenv('SEED_ADMIN_EMAIL') ?: 'admin@syntaxcore.com';
    $password = getenv('SEED_ADMIN_PASSWORD') ?: 'admin123';
    $name = getenv('SEED_ADMIN_NAME') ?: 'Administrator';
    $superadminRole = $rolesMap['superadmin'] ?? null;

    $user = User::findByEmail($email);

    if (!$user) {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'role_id' => $superadminRole?->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword($password);
        $user->save();

        echo "[SUCCESS] Administrator seeded successfully!\n";
        echo "  Name    : {$name}\n";
        echo "  Email   : {$email}\n";
        echo "  Role    : Super Administrator (superadmin)\n";
        echo "  Password: [PROTECTED / CONFIGURABLE VIA SEED_ADMIN_PASSWORD]\n";
    } else {
        // Jika user sudah ada tetapi belum punya role_id, assign role superadmin
        if (empty($user->role_id) && $superadminRole) {
            $user->update(['role_id' => $superadminRole->id]);
            echo "[UPDATE] Assigned role 'superadmin' to existing user '{$email}'.\n";
        } else {
            echo "[INFO] Administrator '{$email}' already exists with role '{$user->roleSlug()}'.\n";
        }
    }

    // 3. Seed Demo Users for Testing Other Levels
    $demoUsers = [
        [
            'name' => 'Manager Staff',
            'email' => 'manager@syntaxcore.com',
            'password' => 'manager123',
            'role_slug' => 'admin',
        ],
        [
            'name' => 'Regular User',
            'email' => 'user@syntaxcore.com',
            'password' => 'user123',
            'role_slug' => 'user',
        ],
    ];

    foreach ($demoUsers as $demo) {
        $existing = User::findByEmail($demo['email']);
        $role = $rolesMap[$demo['role_slug']] ?? null;
        if (!$existing && $role) {
            $u = new User([
                'name' => $demo['name'],
                'email' => $demo['email'],
                'role_id' => $role->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $u->setPassword($demo['password']);
            $u->save();
            echo "[SUCCESS] Demo user seeded: {$demo['email']} ({$demo['role_slug']})\n";
        }
    }

    // 4. Pastikan user lain yang ada di database juga mendapatkan role default jika masih null
    $allUsers = User::all();
    foreach ($allUsers as $u) {
        if (empty($u->role_id)) {
            // Berikan role superadmin untuk admin@gmail.com, atau admin role untuk lainnya
            $assignedRole = ($u->email === 'admin@gmail.com' || str_contains($u->email, 'admin'))
                ? ($rolesMap['superadmin'] ?? $rolesMap['admin'])
                : ($rolesMap['user'] ?? null);

            if ($assignedRole) {
                $u->update(['role_id' => $assignedRole->id]);
                echo "[UPDATE] Assigned role '{$assignedRole->slug}' to user '{$u->email}'.\n";
            }
        }
    }

    // 5. Seed Menus (Root and Nested Submenus) & Hubungkan Hak Akses (role_menu)
    $menuTreeDefs = [
        [
            'title' => 'Dashboard Overview',
            'icon' => 'fa-solid fa-gauge-high',
            'action' => 'open_dashboard',
            'route' => '/admin',
            'badge' => null,
            'sort_order' => 1,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Profil Saya',
            'icon' => 'fa-solid fa-user-gear',
            'action' => 'open_profile',
            'route' => '/admin/profile',
            'badge' => null,
            'sort_order' => 2,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Master Data',
            'icon' => 'fa-solid fa-folder-tree',
            'action' => null,
            'route' => null,
            'badge' => '2',
            'sort_order' => 3,
            'roles' => ['admin', 'superadmin'],
            'children' => [
                [
                    'title' => 'Manajemen Pengguna',
                    'icon' => 'fa-solid fa-users',
                    'action' => 'open_users',
                    'route' => '/admin/users',
                    'badge' => null,
                    'sort_order' => 1,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Data Peran (Roles)',
                    'icon' => 'fa-solid fa-user-shield',
                    'action' => 'open_roles',
                    'route' => '/admin/roles',
                    'badge' => null,
                    'sort_order' => 2,
                    'roles' => ['admin', 'superadmin'],
                ],
            ],
        ],
        [
            'title' => 'Laporan Aktivitas',
            'icon' => 'fa-solid fa-chart-line',
            'action' => 'open_reports',
            'route' => '/admin/reports',
            'badge' => null,
            'sort_order' => 4,
            'roles' => ['admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Sistem & Konfigurasi',
            'icon' => 'fa-solid fa-server',
            'action' => null,
            'route' => null,
            'badge' => '2',
            'sort_order' => 5,
            'roles' => ['superadmin'],
            'children' => [
                [
                    'title' => 'Database Explorer',
                    'icon' => 'fa-solid fa-database',
                    'action' => 'open_database',
                    'route' => '/admin/database',
                    'badge' => null,
                    'sort_order' => 1,
                    'roles' => ['superadmin'],
                ],
                [
                    'title' => 'Pengaturan Sistem',
                    'icon' => 'fa-solid fa-gears',
                    'action' => 'open_settings',
                    'route' => '/admin/settings',
                    'badge' => null,
                    'sort_order' => 2,
                    'roles' => ['superadmin'],
                ],
            ],
        ],
        [
            'title' => 'Dokumentasi Framework',
            'icon' => 'fa-solid fa-book-open',
            'action' => 'new_tab',
            'route' => 'https://github.com',
            'badge' => 'Docs',
            'sort_order' => 6,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
    ];

    $pdo = \Core\Database\Connection::get();

    $seedMenuRecursive = function (array $def, ?int $parentId = null) use (&$seedMenuRecursive, $pdo, $rolesMap) {
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE title = ? AND " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " LIMIT 1");
        if ($parentId === null) {
            $stmt->execute([$def['title']]);
        } else {
            $stmt->execute([$def['title'], $parentId]);
        }
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $insert = $pdo->prepare("INSERT INTO menus (parent_id, title, icon, action, route, badge, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $insert->execute([
                $parentId,
                $def['title'],
                $def['icon'],
                $def['action'] ?? null,
                $def['route'] ?? null,
                $def['badge'] ?? null,
                $def['sort_order'],
            ]);
            $menuId = (int) $pdo->lastInsertId();
            echo "[SUCCESS] Menu seeded: " . ($parentId ? "  └─ " : "") . "{$def['title']}\n";
        } else {
            $menuId = (int) $existing['id'];
            $update = $pdo->prepare("UPDATE menus SET icon = ?, action = ?, route = ?, badge = ?, sort_order = ? WHERE id = ?");
            $update->execute([
                $def['icon'],
                $def['action'] ?? null,
                $def['route'] ?? null,
                $def['badge'] ?? null,
                $def['sort_order'],
                $menuId,
            ]);
        }

        // Hubungkan role_menu
        foreach ($def['roles'] ?? [] as $roleSlug) {
            $role = $rolesMap[$roleSlug] ?? null;
            if ($role) {
                $linkStmt = $pdo->prepare("INSERT IGNORE INTO role_menu (role_id, menu_id) VALUES (?, ?)");
                $linkStmt->execute([$role->id, $menuId]);
            }
        }

        // Rekursif ke children jika ada
        if (!empty($def['children'])) {
            foreach ($def['children'] as $childDef) {
                $seedMenuRecursive($childDef, $menuId);
            }
        }
    };

    foreach ($menuTreeDefs as $rootMenuDef) {
        $seedMenuRecursive($rootMenuDef, null);
    }

    echo "[SUCCESS] All menus and role mappings seeded successfully!\n";
} catch (\Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
