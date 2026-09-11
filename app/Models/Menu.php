<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class Menu extends Model
{
    protected ?string $table = 'menus';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'parent_id',
        'title',
        'icon',
        'action',
        'route',
        'badge',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Ambil model parent dari menu ini (jika ada).
     */
    public function parent(): ?self
    {
        if (empty($this->parent_id)) {
            return null;
        }
        return static::find((int) $this->parent_id);
    }

    /**
     * Ambil anak-anak (sub-items) dari menu ini.
     */
    public function children(): array
    {
        $table = static::escapeIdentifier($this->getTable());
        $sql = "SELECT * FROM {$table} WHERE `parent_id` = ? AND `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC";
        $rows = Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            return $model;
        }, $rows);
    }

    /**
     * Cek apakah menu ini memiliki anak menu (sub-items).
     */
    public function hasChildren(): bool
    {
        $table = static::escapeIdentifier($this->getTable());
        $row = Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM {$table} WHERE `parent_id` = ? AND `is_active` = 1",
            [$this->id],
            $this->connection
        );
        return ((int) ($row['cnt'] ?? 0)) > 0;
    }

    /**
     * Ambil semua roles yang diizinkan mengakses menu ini via role_menu.
     */
    public function roles(): array
    {
        $sql = "SELECT r.* FROM `roles` r INNER JOIN `role_menu` rm ON r.id = rm.role_id WHERE rm.menu_id = ?";
        $rows = Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $role = new Role();
            $role->forceFill($row);
            $role->original = $row;
            return $role;
        }, $rows);
    }
}
