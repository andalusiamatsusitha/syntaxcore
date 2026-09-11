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

        \App\Services\ActivityLogger::log(
            'user.create',
            "Menambahkan pengguna baru: {$user->name} ({$user->email})",
            $this->auth->id(),
            $request
        );
        $roleTitle = $user->role()?->name ?? 'User';
        \App\Services\ActivityLogger::notify(
            'Pengguna Baru Dibuat',
            "Akun pengguna {$user->name} ({$roleTitle}) berhasil ditambahkan ke sistem.",
            'success'
        );

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

        \App\Services\ActivityLogger::log(
            'user.update',
            "Memperbarui data pengguna: {$user->name} ({$user->email})",
            $this->auth->id(),
            $request
        );

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

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        \App\Services\ActivityLogger::log(
            'user.delete',
            "Menghapus pengguna: {$userName} ({$userEmail})",
            $this->auth->id(),
            $request
        );
        \App\Services\ActivityLogger::notify(
            'Pengguna Dihapus',
            "Akun pengguna {$userName} telah dihapus dari sistem.",
            'warning'
        );

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

        \App\Services\ActivityLogger::log(
            'role.create',
            "Menambahkan peran baru: {$role->name} ({$role->slug}, Level {$role->level})",
            $this->auth->id(),
            $request
        );
        \App\Services\ActivityLogger::notify(
            'Peran Baru Ditambahkan',
            "Peran {$role->name} berhasil dibuat dengan akses ke " . count($role->menuIds()) . " menu.",
            'success'
        );

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

        \App\Services\ActivityLogger::log(
            'role.update',
            "Memperbarui data peran & hak akses: {$role->name} (#{$role->id})",
            $this->auth->id(),
            $request
        );

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

        $roleName = $role->name;
        $role->delete();

        \App\Services\ActivityLogger::log(
            'role.delete',
            "Menghapus peran: {$roleName} (#{$id})",
            $this->auth->id(),
            $request
        );
        \App\Services\ActivityLogger::notify(
            'Peran Dihapus',
            "Peran {$roleName} telah dihapus dari sistem.",
            'warning'
        );

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
        $actionFilter = trim((string) $request->input('action', ''));
        $searchQuery = trim((string) $request->input('q', ''));

        $sql = "SELECT al.*, u.name as user_name, u.email as user_email, r.name as role_name 
                FROM `activity_logs` al 
                LEFT JOIN `users` u ON al.user_id = u.id 
                LEFT JOIN `roles` r ON u.role_id = r.id 
                WHERE 1=1";
        $bindings = [];

        if (!empty($actionFilter)) {
            $sql .= " AND al.action LIKE ?";
            $bindings[] = "{$actionFilter}%";
        }

        if (!empty($searchQuery)) {
            $sql .= " AND (al.description LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR al.ip_address LIKE ?)";
            $bindings[] = "%{$searchQuery}%";
            $bindings[] = "%{$searchQuery}%";
            $bindings[] = "%{$searchQuery}%";
            $bindings[] = "%{$searchQuery}%";
        }

        $sql .= " ORDER BY al.id DESC LIMIT 100";
        $rows = \Core\Database\Connection::select($sql, $bindings);

        $todayCount = (int) (\Core\Database\Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM `activity_logs` WHERE DATE(created_at) = CURDATE()"
        )['cnt'] ?? 0);

        $totalCount = (int) (\Core\Database\Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM `activity_logs`"
        )['cnt'] ?? 0);

        $logs = array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'user_id' => $row['user_id'] ? (int) $row['user_id'] : null,
                'user_name' => $row['user_name'] ?? 'Sistem / Guest',
                'user_email' => $row['user_email'] ?? '-',
                'role_name' => $row['role_name'] ?? '-',
                'action' => $row['action'],
                'description' => $row['description'],
                'ip_address' => $row['ip_address'] ?? '127.0.0.1',
                'user_agent' => $row['user_agent'] ?? '-',
                'created_at' => $row['created_at'],
            ];
        }, $rows);

        return $this->json([
            'status' => 'success',
            'module' => 'Laporan Aktivitas',
            'authorized_role' => $this->auth->user()?->roleSlug(),
            'total' => $totalCount,
            'today_count' => $todayCount,
            'filtered_count' => count($logs),
            'logs' => $logs,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => 'Ringkasan aktivitas sistem dan log transaksi pengguna.',
        ]);
    }

    /**
     * Endpoint Ambil Notifikasi Pengguna (GET /admin/notifications)
     */
    public function notifications(Request $request): Response
    {
        $userId = $this->auth->id();

        $sql = "SELECT * FROM `notifications` 
                WHERE user_id IS NULL OR user_id = ? 
                ORDER BY id DESC LIMIT 30";
        $rows = \Core\Database\Connection::select($sql, [$userId]);

        $unreadSql = "SELECT COUNT(*) as cnt FROM `notifications` 
                      WHERE (user_id IS NULL OR user_id = ?) AND is_read = 0";
        $unreadCount = (int) (\Core\Database\Connection::selectOne($unreadSql, [$userId])['cnt'] ?? 0);

        $notifications = array_map(function ($n) {
            return [
                'id' => (int) $n['id'],
                'user_id' => $n['user_id'] ? (int) $n['user_id'] : null,
                'title' => $n['title'],
                'message' => $n['message'],
                'type' => $n['type'] ?? 'info',
                'is_read' => (bool) $n['is_read'],
                'created_at' => $n['created_at'],
            ];
        }, $rows);

        return $this->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'total' => count($notifications),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Tandai Notifikasi Telah Dibaca (POST /admin/notifications/{id}/read)
     */
    public function markNotificationRead(Request $request): Response
    {
        $id = (int) $request->param('id');
        $userId = $this->auth->id();

        $notif = \App\Models\Notification::find($id);
        if (!$notif) {
            return $this->json([
                'status' => 'error',
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        if ($notif->user_id !== null && (int)$notif->user_id !== (int)$userId) {
            return $this->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $notif->markAsRead();

        return $this->json([
            'status' => 'success',
            'message' => 'Notifikasi ditandai telah dibaca.',
        ]);
    }

    /**
     * Tandai Semua Notifikasi Telah Dibaca (POST /admin/notifications/read-all)
     */
    public function markAllNotificationsRead(Request $request): Response
    {
        $userId = $this->auth->id();

        \Core\Database\Connection::statement(
            "UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` IS NULL OR `user_id` = ?",
            [$userId]
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Semua notifikasi ditandai telah dibaca.',
        ]);
    }

    /**
     * Endpoint Profil Pengguna Saat Ini (GET /admin/profile)
     */
    public function profile(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        // Ambil 5 log aktivitas terakhir milik pengguna ini
        $recentLogs = \Core\Database\Connection::select(
            "SELECT id, action, description, ip_address, created_at 
             FROM `activity_logs` 
             WHERE `user_id` = ? 
             ORDER BY `id` DESC LIMIT 5",
            [$user->id]
        );

        return $this->json([
            'status' => 'success',
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roleSlug(),
                'role_name' => $user->role()?->name ?? 'User',
                'level' => $user->roleLevel(),
                'created_at' => $user->created_at ?? date('Y-m-d H:i:s'),
            ],
            'recent_activities' => array_map(function ($l) {
                return [
                    'id' => (int) $l['id'],
                    'action' => $l['action'],
                    'description' => $l['description'],
                    'ip_address' => $l['ip_address'],
                    'created_at' => $l['created_at'],
                ];
            }, $recentLogs),
        ]);
    }

    /**
     * Update Profil Pengguna Saat Ini (PUT /admin/profile)
     */
    public function updateProfile(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $currentPassword = (string) $request->input('current_password', '');
        $newPassword = (string) $request->input('new_password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');

        // 1. Validasi Nama
        if ($name === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama lengkap tidak boleh kosong.',
            ], 422);
        }
        if (mb_strlen($name) > 100) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nama lengkap maksimal 100 karakter.',
            ], 422);
        }

        // 2. Validasi Format Email
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Format alamat email tidak valid.',
            ], 422);
        }
        if (mb_strlen($email) > 150) {
            return $this->json([
                'status' => 'error',
                'message' => 'Alamat email maksimal 150 karakter.',
            ], 422);
        }

        // 3. Validasi Keunikan Email (kecuali email user sendiri)
        $existing = \Core\Database\Connection::selectOne(
            "SELECT id FROM `users` WHERE `email` = ? AND `id` != ? LIMIT 1",
            [$email, $user->id]
        );
        if ($existing) {
            return $this->json([
                'status' => 'error',
                'message' => "Alamat email '{$email}' sudah digunakan oleh akun lain.",
            ], 422);
        }

        // 4. Validasi Penggantian Password (jika ada input password)
        $passwordChanged = false;
        if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
            if ($currentPassword === '') {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Masukkan password saat ini untuk melakukan penggantian password.',
                ], 422);
            }

            if (!$user->verifyPassword($currentPassword)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Password saat ini yang Anda masukkan tidak sesuai.',
                ], 422);
            }

            if ($newPassword === '') {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Password baru tidak boleh kosong.',
                ], 422);
            }

            if (strlen($newPassword) < 6) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Password baru minimal harus 6 karakter.',
                ], 422);
            }

            if ($newPassword !== $confirmPassword) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Konfirmasi password baru tidak cocok.',
                ], 422);
            }

            $user->setPassword($newPassword);
            $passwordChanged = true;
        }

        $oldName = $user->name;
        $oldEmail = $user->email;

        $user->name = $name;
        $user->email = $email;
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save();

        // 5. Pencatatan Log Aktivitas & Notifikasi
        $detailChanges = [];
        if ($oldName !== $name) {
            $detailChanges[] = "nama ({$oldName} -> {$name})";
        }
        if ($oldEmail !== $email) {
            $detailChanges[] = "email ({$oldEmail} -> {$email})";
        }
        if ($passwordChanged) {
            $detailChanges[] = "kata sandi";
        }

        $desc = empty($detailChanges)
            ? "Pengguna {$name} menyimpan data profil tanpa perubahan data."
            : "Pengguna {$name} memperbarui profil: " . implode(', ', $detailChanges);

        \App\Services\ActivityLogger::log('user.profile_update', $desc, $user->id, $request);

        \App\Services\ActivityLogger::notify(
            'Profil Diperbarui',
            'Data profil Anda berhasil diperbarui di sistem.',
            'success',
            $user->id
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Profil Anda berhasil diperbarui.',
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roleSlug(),
                'role_name' => $user->role()?->name ?? 'User',
                'level' => $user->roleLevel(),
                'created_at' => $user->created_at ?? date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Upload Gambar Background Desktop (POST /admin/wallpaper)
     */
    public function uploadWallpaper(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        $file = $request->file('wallpaper') ?? $_FILES['wallpaper'] ?? null;
        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->json([
                'status' => 'error',
                'message' => 'Silakan pilih berkas gambar untuk diunggah.',
            ], 422);
        }

        // 1. Validasi Ukuran File (Maksimal 10MB)
        $maxSize = 10 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ukuran berkas gambar maksimal 10MB.',
            ], 422);
        }

        // 2. Validasi Ekstensi dan MIME Type
        $originalName = (string) ($file['name'] ?? 'wallpaper.jpg');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Format file tidak didukung. Format yang diizinkan: JPG, PNG, WEBP, GIF, SVG.',
            ], 422);
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/svg'];
            if (!in_array($mime, $allowedMimes, true)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Berkas yang diunggah bukan merupakan gambar yang valid.',
                ], 422);
            }
        }

        // 3. Simpan File ke direktori public/uploads/wallpapers
        $targetDir = dirname(__DIR__, 3) . '/public/uploads/wallpapers';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = 'wp_' . $user->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        // Mendukung file asli dari upload HTTP (move_uploaded_file) atau file temporary testing (rename/copy)
        $moved = is_uploaded_file($file['tmp_name']) 
            ? move_uploaded_file($file['tmp_name'], $targetPath)
            : copy($file['tmp_name'], $targetPath);

        if (!$moved) {
            return $this->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan gambar latar belakang ke penyimpanan server.',
            ], 500);
        }

        $publicUrl = '/uploads/wallpapers/' . $filename;

        // 4. Catat Log Aktivitas
        \App\Services\ActivityLogger::log(
            'desktop.wallpaper_update',
            "Pengguna {$user->name} memperbarui gambar latar belakang desktop",
            $user->id,
            $request
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Gambar latar belakang desktop berhasil diunggah.',
            'url' => $publicUrl,
            'filename' => $filename,
        ]);
    }

    /**
     * Reset Background Desktop (DELETE /admin/wallpaper)
     */
    public function deleteWallpaper(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        \App\Services\ActivityLogger::log(
            'desktop.wallpaper_reset',
            "Pengguna {$user->name} mengembalikan latar belakang desktop ke default",
            $user->id,
            $request
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Latar belakang desktop berhasil dikembalikan ke tampilan default.',
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
