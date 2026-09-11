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
                'role_id' => $u->role_id,
                'level' => $u->roleLevel(),
            ];
        }, $users);

        $roles = array_map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'level' => $r->level,
            ];
        }, \App\Models\Role::all());

        return $this->json([
            'status' => 'success',
            'module' => 'Manajemen Pengguna',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'total' => count($userList),
            'users' => $userList,
            'roles' => $roles,
        ]);
    }

    /**
     * Tambah Pengguna Baru (POST /admin/users)
     */
    public function storeUser(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $roleId = (int) $request->input('role_id', 3);

        if (empty($name) || empty($email) || empty($password)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama, email, dan password wajib diisi.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Format email tidak valid.',
            ], 422);
        }

        if (\App\Models\User::findByEmail($email)) {
            return $this->json([
                'status' => 'error',
                'message' => "Email {$email} sudah digunakan oleh akun lain.",
            ], 422);
        }

        $user = new \App\Models\User([
            'name' => $name,
            'email' => $email,
            'role_id' => $roleId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword($password);
        $user->save();

        return $this->json([
            'status' => 'success',
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roleSlug(),
                'role_name' => $user->role()?->name ?? 'User',
            ],
        ], 201);
    }

    /**
     * Perbarui Data Pengguna (PUT /admin/users/{id})
     */
    public function updateUser(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = \App\Models\User::find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $name = trim((string) $request->input('name', $user->name));
        $email = strtolower(trim((string) $request->input('email', $user->email)));
        $roleId = (int) $request->input('role_id', $user->role_id);
        $password = (string) $request->input('password', '');

        if (empty($name) || empty($email)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama dan email wajib diisi.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Format email tidak valid.',
            ], 422);
        }

        $existing = \App\Models\User::findByEmail($email);
        if ($existing && (int)$existing->id !== (int)$user->id) {
            return $this->json([
                'status' => 'error',
                'message' => "Email {$email} sudah digunakan oleh akun lain.",
            ], 422);
        }

        $user->name = $name;
        $user->email = $email;
        $user->role_id = $roleId;
        $user->updated_at = date('Y-m-d H:i:s');

        if (!empty($password)) {
            $user->setPassword($password);
        }

        $user->save();

        return $this->json([
            'status' => 'success',
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roleSlug(),
                'role_name' => $user->role()?->name ?? 'User',
            ],
        ]);
    }

    /**
     * Hapus Pengguna (DELETE /admin/users/{id})
     */
    public function deleteUser(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = \App\Models\User::find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        // Cegah menghapus diri sendiri yang sedang aktif
        if ($this->auth->id() === $id) {
            return $this->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.',
            ], 422);
        }

        $user->delete();

        return $this->json([
            'status' => 'success',
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * Endpoint Manajemen Peran & Hak Akses (Accessible by: admin, superadmin)
     */
    public function roles(Request $request): Response
    {
        $roles = \App\Models\Role::all();
        $roleList = array_map(function ($r) {
            $menuIds = $r->menuIds();
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'level' => (int) $r->level,
                'description' => $r->description ?? '',
                'user_count' => $r->userCount(),
                'menu_ids' => $menuIds,
                'menu_count' => count($menuIds),
            ];
        }, $roles);

        // Ambil semua menu yang aktif untuk permission matrix
        $sql = "SELECT id, parent_id, title, icon, action, route, sort_order FROM `menus` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC";
        $menuList = \Core\Database\Connection::select($sql);
        $cleanMenus = array_map(function ($m) {
            return [
                'id' => (int) $m['id'],
                'parent_id' => $m['parent_id'] !== null ? (int) $m['parent_id'] : null,
                'title' => $m['title'],
                'icon' => $m['icon'] ?? 'fa-solid fa-circle-notch',
                'action' => $m['action'],
                'route' => $m['route'],
                'sort_order' => (int) $m['sort_order'],
            ];
        }, $menuList);

        return $this->json([
            'status' => 'success',
            'module' => 'Data Peran & Hak Akses',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'authorized_level' => $this->auth->user()?->roleLevel() ?? 1,
            'total' => count($roleList),
            'roles' => $roleList,
            'menus' => $cleanMenus,
        ]);
    }

    /**
     * Tambah Peran Baru beserta Hak Akses Menu (POST /admin/roles)
     */
    public function storeRole(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $slug = trim((string) $request->input('slug', ''));
        $level = (int) $request->input('level', 1);
        $description = trim((string) $request->input('description', ''));
        $menuIds = $request->input('menu_ids', []);
        if (!is_array($menuIds)) {
            $menuIds = [];
        }

        if (empty($name)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama peran wajib diisi.',
            ], 422);
        }

        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
            $slug = trim($slug, '-');
        } else {
            $slug = preg_replace('/[^a-z0-9_\-]+/', '-', strtolower($slug));
            $slug = trim($slug, '-');
        }

        if (empty($slug)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Slug peran tidak valid.',
            ], 422);
        }

        if (\App\Models\Role::findBySlug($slug)) {
            return $this->json([
                'status' => 'error',
                'message' => "Slug '{$slug}' sudah digunakan oleh peran lain.",
            ], 422);
        }

        $currentUserLevel = $this->auth->user()?->roleLevel() ?? 1;
        if ($level > $currentUserLevel) {
            return $this->json([
                'status' => 'error',
                'message' => "Anda tidak memiliki wewenang untuk membuat peran dengan level {$level}.",
            ], 403);
        }

        if ($level < 1) {
            $level = 1;
        }

        $role = new \App\Models\Role([
            'name' => $name,
            'slug' => $slug,
            'level' => $level,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $role->save();

        // Sync hak akses menu jika ada
        $role->syncMenus($menuIds);

        return $this->json([
            'status' => 'success',
            'message' => 'Peran baru berhasil ditambahkan.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'level' => (int) $role->level,
                'description' => $role->description,
                'menu_ids' => $role->menuIds(),
            ],
        ], 201);
    }

    /**
     * Perbarui Data Peran & Hak Akses (PUT /admin/roles/{id})
     */
    public function updateRole(Request $request): Response
    {
        $id = (int) $request->param('id');
        $role = \App\Models\Role::find($id);

        if (!$role) {
            return $this->json([
                'status' => 'error',
                'message' => 'Peran tidak ditemukan.',
            ], 404);
        }

        $currentUserLevel = $this->auth->user()?->roleLevel() ?? 1;

        // Cegah pengguna mengubah peran dengan tingkat otoritas lebih tinggi dari dirinya sendiri
        if ($role->level > $currentUserLevel) {
            return $this->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki wewenang untuk mengubah peran dengan tingkat otoritas lebih tinggi.',
            ], 403);
        }

        $name = trim((string) $request->input('name', $role->name));
        $slug = trim((string) $request->input('slug', $role->slug));
        $level = (int) $request->input('level', $role->level);
        $description = trim((string) $request->input('description', $role->description ?? ''));
        $allInputs = $request->all();
        $hasMenuIds = array_key_exists('menu_ids', $allInputs);
        $menuIds = $request->input('menu_ids', []);
        if (!is_array($menuIds)) {
            $menuIds = [];
        }

        if (empty($name)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama peran wajib diisi.',
            ], 422);
        }

        // Superadmin memiliki batasan perlindungan slug & level
        if ($role->slug === 'superadmin') {
            $slug = 'superadmin';
            $level = 3;
        } else {
            if (empty($slug)) {
                $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
                $slug = trim($slug, '-');
            } else {
                $slug = preg_replace('/[^a-z0-9_\-]+/', '-', strtolower($slug));
                $slug = trim($slug, '-');
            }

            $existing = \App\Models\Role::findBySlug($slug);
            if ($existing && (int)$existing->id !== (int)$role->id) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Slug '{$slug}' sudah digunakan oleh peran lain.",
                ], 422);
            }

            if ($level > $currentUserLevel) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Anda tidak dapat menaikkan peran ke level {$level} yang melebihi hak akses Anda.",
                ], 403);
            }

            if ($level < 1) {
                $level = 1;
            }
        }

        $role->name = $name;
        $role->slug = $slug;
        $role->level = $level;
        $role->description = $description;
        $role->updated_at = date('Y-m-d H:i:s');
        $role->save();

        if ($hasMenuIds) {
            $role->syncMenus($menuIds);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Data peran berhasil diperbarui.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'level' => (int) $role->level,
                'description' => $role->description,
                'menu_ids' => $role->menuIds(),
            ],
        ]);
    }

    /**
     * Hapus Peran (DELETE /admin/roles/{id})
     */
    public function deleteRole(Request $request): Response
    {
        $id = (int) $request->param('id');
        $role = \App\Models\Role::find($id);

        if (!$role) {
            return $this->json([
                'status' => 'error',
                'message' => 'Peran tidak ditemukan.',
            ], 404);
        }

        // Cegah menghapus superadmin
        if ($role->slug === 'superadmin' || $role->level >= 3) {
            return $this->json([
                'status' => 'error',
                'message' => 'Peran Super Administrator adalah peran sistem yang diproteksi dan tidak dapat dihapus.',
            ], 422);
        }

        // Cegah menghapus peran yang sedang digunakan oleh user saat ini
        if ($this->auth->user()?->role_id === $id) {
            return $this->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat menghapus peran yang sedang aktif Anda gunakan.',
            ], 422);
        }

        // Cegah menghapus jika masih ada user yang terikat
        $userCount = $role->userCount();
        if ($userCount > 0) {
            return $this->json([
                'status' => 'error',
                'message' => "Peran '{$role->name}' tidak dapat dihapus karena masih digunakan oleh {$userCount} pengguna.",
            ], 422);
        }

        // Hapus relasi hak akses menu di role_menu
        \Core\Database\Connection::statement("DELETE FROM `role_menu` WHERE `role_id` = ?", [$id]);

        $role->delete();

        return $this->json([
            'status' => 'success',
            'message' => 'Peran berhasil dihapus.',
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
