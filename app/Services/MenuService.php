<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Core\Database\Connection;

class MenuService
{
    /**
     * Ambil struktur pohon menu berjenjang (Nested Tree) untuk user yang sedang login.
     */
    public function getMenusForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        $menus = $this->fetchAllowedMenusForUser($user);

        return $this->buildTree($menus, null);
    }

    /**
     * Query data mentah menu dari database sesuai hak akses role user.
     */
    protected function fetchAllowedMenusForUser(User $user): array
    {
        // Jika superadmin (level >= 3), dapatkan semua menu aktif
        if ($user->hasRole('superadmin') || $user->roleLevel() >= 3) {
            $sql = "SELECT * FROM `menus` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC";
            return Connection::select($sql);
        }

        // Untuk role lain, ambil menu yang terdaftar di role_menu
        $roleId = $user->role_id;
        if (empty($roleId)) {
            return [];
        }

        $sql = "SELECT m.* FROM `menus` m 
                INNER JOIN `role_menu` rm ON m.id = rm.menu_id 
                WHERE rm.role_id = ? AND m.is_active = 1 
                ORDER BY m.sort_order ASC, m.id ASC";

        return Connection::select($sql, [$roleId]);
    }

    /**
     * Algoritma rekursif untuk mengubah flat array database menjadi nested tree berjenjang.
     *
     * @param array $elements Flat array dari database
     * @param int|null $parentId ID induk (null untuk root)
     * @return array Nested array
     */
    public function buildTree(array $elements, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($elements as $element) {
            $elemParentId = $element['parent_id'] !== null ? (int) $element['parent_id'] : null;

            if ($elemParentId === $parentId) {
                // Cari anak-anaknya secara rekursif
                $children = $this->buildTree($elements, (int) $element['id']);

                $item = [
                    'id' => (int) $element['id'],
                    'parent_id' => $elemParentId,
                    'title' => (string) $element['title'],
                    'icon' => $element['icon'] ?? 'fa-solid fa-circle-notch',
                    'action' => $element['action'] ?: null,
                    'route' => $element['route'] ?: null,
                    'badge' => $element['badge'] ?: null,
                    'sort_order' => (int) ($element['sort_order'] ?? 0),
                    'children' => $children,
                ];

                $branch[] = $item;
            }
        }

        return $branch;
    }
}

