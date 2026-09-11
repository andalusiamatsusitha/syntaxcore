<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class Tag extends Model
{
    protected ?string $table = 'tags';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'slug',
        'created_at',
        'updated_at',
    ];

    public static function findBySlug(string $slug): ?static
    {
        $results = static::where('slug', '=', $slug);
        return $results[0] ?? null;
    }

    /**
     * Cari atau buat tag baru berdasarkan nama.
     */
    public static function firstOrCreateByName(string $name): static
    {
        $name = trim($name);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        $existing = static::findBySlug($slug);
        if ($existing) {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        return static::create([
            'name' => $name,
            'slug' => $slug,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Dapatkan daftar berita yang terkait dengan tag ini.
     *
     * @return News[]
     */
    public function news(): array
    {
        $sql = "SELECT n.* FROM `news` n
                INNER JOIN `news_tag` nt ON n.id = nt.news_id
                WHERE nt.tag_id = ?
                ORDER BY n.published_at DESC, n.id DESC";

        $rows = Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $news = new News();
            $news->forceFill($row);
            $news->original = $row;
            return $news;
        }, $rows);
    }

    public function newsCount(): int
    {
        $res = Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM `news_tag` WHERE `tag_id` = ?",
            [$this->id],
            $this->connection
        );
        return (int) ($res['cnt'] ?? 0);
    }
}
