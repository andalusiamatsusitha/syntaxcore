<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class Category extends Model
{
    protected ?string $table = 'categories';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'parent_id',
        'name',
        'slug',
        'color',
        'description',
        'created_at',
        'updated_at',
    ];

    public static function findBySlug(string $slug): ?static
    {
        $results = static::where('slug', '=', $slug);
        return $results[0] ?? null;
    }

    public function parent(): ?Category
    {
        if (empty($this->parent_id)) {
            return null;
        }
        return static::find($this->parent_id);
    }

    public function children(): array
    {
        return static::where('parent_id', '=', $this->id);
    }

    public function newsCount(): int
    {
        $res = Connection::selectOne("SELECT COUNT(*) as cnt FROM `news` WHERE `category_id` = ?", [$this->id]);
        return (int) ($res['cnt'] ?? 0);
    }
}
