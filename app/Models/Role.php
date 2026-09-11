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
}
