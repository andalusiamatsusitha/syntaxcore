<?php

namespace App\Models;

use Core\Database\Model;

class Role extends Model
{
    protected ?string $table = 'roles';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'slug',
        'level',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a role by its slug identifier (e.g. 'superadmin', 'admin', 'user').
     */
    public static function findBySlug(string $slug): ?static
    {
        $results = static::where('slug', '=', $slug);
        return $results[0] ?? null;
    }

    /**
     * Ambil semua menu yang diizinkan untuk role ini.
     */
    public function menus(): array
    {
        $sql = "SELECT m.* FROM `menus` m INNER JOIN `role_menu` rm ON m.id = rm.menu_id WHERE rm.role_id = ? AND m.is_active = 1 ORDER BY m.sort_order ASC, m.id ASC";
        $rows = \Core\Database\Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $menu = new Menu();
            $menu->forceFill($row);
            $menu->original = $row;
            return $menu;
        }, $rows);
    }

    /**
     * Ambil array ID menu yang terkait dengan role ini.
     */
    public function menuIds(): array
    {
        $sql = "SELECT menu_id FROM `role_menu` WHERE `role_id` = ?";
        $rows = \Core\Database\Connection::select($sql, [$this->id], $this->connection);
        return array_map(fn($row) => (int) $row['menu_id'], $rows);
    }

    /**
     * Hitung jumlah pengguna yang terikat dengan role ini.
     */
    public function userCount(): int
    {
        $row = \Core\Database\Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM `users` WHERE `role_id` = ?",
            [$this->id],
            $this->connection
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Sinkronisasi izin menu untuk role ini di tabel pivot role_menu.
     */
    public function syncMenus(array $menuIds): void
    {
        \Core\Database\Connection::statement(
            "DELETE FROM `role_menu` WHERE `role_id` = ?",
            [$this->id],
            $this->connection
        );

        $cleanIds = array_unique(array_filter(array_map('intval', $menuIds), fn($id) => $id > 0));
        foreach ($cleanIds as $mId) {
            \Core\Database\Connection::statement(
                "INSERT INTO `role_menu` (`role_id`, `menu_id`) VALUES (?, ?)",
                [$this->id, $mId],
                $this->connection
            );
        }
    }
}

