<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class PublicMenu extends Model
{
    protected ?string $table = 'public_menus';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'parent_id',
        'title',
        'link_type',
        'link_target',
        'url',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function parent(): ?self
    {
        if (empty($this->parent_id)) {
            return null;
        }
        return static::find((int) $this->parent_id);
    }

    /**
     * Dapatkan sub-menu dari menu publik ini.
     *
     * @return PublicMenu[]
     */
    public function children(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM `public_menus` WHERE `parent_id` = ?";
        if ($onlyActive) {
            $sql .= " AND `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";

        $rows = Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            return $model;
        }, $rows);
    }

    public function hasChildren(bool $onlyActive = true): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM `public_menus` WHERE `parent_id` = ?";
        if ($onlyActive) {
            $sql .= " AND `is_active` = 1";
        }
        $row = Connection::selectOne($sql, [$this->id], $this->connection);
        return ((int) ($row['cnt'] ?? 0)) > 0;
    }

    /**
     * Generate URL aktif berdasarkan tipe link.
     */
    public function getComputedUrl(): string
    {
        if (!empty($this->url)) {
            return $this->url;
        }

        $type = $this->link_type ?? 'page';
        $target = trim((string) $this->link_target, '/');

        return match ($type) {
            'custom_url' => $this->url ?: '/' . $target,
            'news_category' => '/berita/kategori/' . $target,
            'news_tag' => '/berita/tag/' . $target,
            'news_index' => '/berita',
            'page' => ($target === '' || $target === 'beranda') ? '/' : '/' . $target,
            default => '/' . $target,
        };
    }

    /**
     * Bangun hierarki pohon menu publik (nested tree).
     *
     * @param bool $onlyActive
     * @return array
     */
    public static function tree(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM `public_menus`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `parent_id` ASC, `sort_order` ASC, `id` ASC";

        $rows = Connection::select($sql);

        $items = [];
        foreach ($rows as $row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            $data = $model->toArray();
            $data['computed_url'] = $model->getComputedUrl();
            $data['children'] = [];
            $items[$model->id] = $data;
        }

        $tree = [];
        foreach ($items as $id => &$item) {
            $parentId = $item['parent_id'];
            if (!empty($parentId) && isset($items[$parentId])) {
                $items[$parentId]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }

        return $tree;
    }
}
