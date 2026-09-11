<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class News extends Model
{
    protected ?string $table = 'news';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'summary',
        'content',
        'featured_image',
        'allow_comments',
        'views_count',
        'status',
        'published_at',
        'created_at',
        'updated_at',
    ];

    public static function findBySlug(string $slug): ?static
    {
        $results = static::where('slug', '=', $slug);
        return $results[0] ?? null;
    }

    public function category(): ?Category
    {
        if (empty($this->category_id)) {
            return null;
        }
        return Category::find($this->category_id);
    }

    public function author(): ?User
    {
        if (empty($this->user_id)) {
            return null;
        }
        return User::find($this->user_id);
    }

    /**
     * Dapatkan tag yang diasosiasikan dengan berita ini.
     *
     * @return Tag[]
     */
    public function tags(): array
    {
        $sql = "SELECT t.* FROM `tags` t
                INNER JOIN `news_tag` nt ON t.id = nt.tag_id
                WHERE nt.news_id = ?
                ORDER BY t.name ASC";

        $rows = Connection::select($sql, [$this->id], $this->connection);

        return array_map(function ($row) {
            $tag = new Tag();
            $tag->forceFill($row);
            $tag->original = $row;
            return $tag;
        }, $rows);
    }

    /**
     * Sinkronisasi tag id pada pivot news_tag.
     *
     * @param int[] $tagIds
     */
    public function syncTags(array $tagIds): void
    {
        if (empty($this->id)) {
            return;
        }

        Connection::statement("DELETE FROM `news_tag` WHERE `news_id` = ?", [$this->id], $this->connection);

        $tagIds = array_unique(array_filter(array_map('intval', $tagIds)));
        if (!empty($tagIds)) {
            $placeholders = [];
            $values = [];
            foreach ($tagIds as $tid) {
                $placeholders[] = "(?, ?)";
                $values[] = $this->id;
                $values[] = $tid;
            }
            $sql = "INSERT INTO `news_tag` (`news_id`, `tag_id`) VALUES " . implode(', ', $placeholders);
            Connection::statement($sql, $values, $this->connection);
        }
    }

    /**
     * Dapatkan komentar untuk berita ini.
     *
     * @param bool $onlyApproved Hanya tampilkan komentar yang status = 'approved'
     * @return Comment[]
     */
    public function comments(bool $onlyApproved = true): array
    {
        $sql = "SELECT * FROM `comments` WHERE `news_id` = ?";
        $params = [$this->id];

        if ($onlyApproved) {
            $sql .= " AND `status` = 'approved'";
        }

        $sql .= " ORDER BY `created_at` ASC, `id` ASC";

        $rows = Connection::select($sql, $params, $this->connection);

        return array_map(function ($row) {
            $comment = new Comment();
            $comment->forceFill($row);
            $comment->original = $row;
            return $comment;
        }, $rows);
    }

    public function approvedCommentsCount(): int
    {
        $res = Connection::selectOne(
            "SELECT COUNT(*) as cnt FROM `comments` WHERE `news_id` = ? AND `status` = 'approved'",
            [$this->id],
            $this->connection
        );
        return (int) ($res['cnt'] ?? 0);
    }

    public function incrementViews(): void
    {
        if (!empty($this->id)) {
            Connection::statement(
                "UPDATE `news` SET `views_count` = `views_count` + 1 WHERE `id` = ?",
                [$this->id],
                $this->connection
            );
            $this->views_count = ((int) $this->views_count) + 1;
        }
    }

    /**
     * Dapatkan daftar berita terpublikasi dengan filter kategori/tag dan pagination.
     */
    public static function getPublishedNews(
        int $limit = 10,
        int $offset = 0,
        ?int $categoryId = null,
        ?int $tagId = null,
        ?string $search = null
    ): array {
        $sql = "SELECT DISTINCT n.* FROM `news` n";
        $params = [];

        if ($tagId !== null) {
            $sql .= " INNER JOIN `news_tag` nt ON n.id = nt.news_id";
        }

        $sql .= " WHERE n.`status` = 'published' AND (n.`published_at` IS NULL OR n.`published_at` <= NOW())";

        if ($categoryId !== null) {
            $sql .= " AND n.`category_id` = ?";
            $params[] = $categoryId;
        }

        if ($tagId !== null) {
            $sql .= " AND nt.`tag_id` = ?";
            $params[] = $tagId;
        }

        if (!empty($search)) {
            $sql .= " AND (n.`title` LIKE ? OR n.`summary` LIKE ? OR n.`content` LIKE ?)";
            $searchWildcard = '%' . $search . '%';
            $params[] = $searchWildcard;
            $params[] = $searchWildcard;
            $params[] = $searchWildcard;
        }

        $sql .= " ORDER BY n.`published_at` DESC, n.`id` DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $rows = Connection::select($sql, $params);

        return array_map(function ($row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            return $model;
        }, $rows);
    }

    /**
     * Hitung total berita terpublikasi dengan filter.
     */
    public static function countPublishedNews(
        ?int $categoryId = null,
        ?int $tagId = null,
        ?string $search = null
    ): int {
        $sql = "SELECT COUNT(DISTINCT n.id) as cnt FROM `news` n";
        $params = [];

        if ($tagId !== null) {
            $sql .= " INNER JOIN `news_tag` nt ON n.id = nt.news_id";
        }

        $sql .= " WHERE n.`status` = 'published' AND (n.`published_at` IS NULL OR n.`published_at` <= NOW())";

        if ($categoryId !== null) {
            $sql .= " AND n.`category_id` = ?";
            $params[] = $categoryId;
        }

        if ($tagId !== null) {
            $sql .= " AND nt.`tag_id` = ?";
            $params[] = $tagId;
        }

        if (!empty($search)) {
            $sql .= " AND (n.`title` LIKE ? OR n.`summary` LIKE ? OR n.`content` LIKE ?)";
            $searchWildcard = '%' . $search . '%';
            $params[] = $searchWildcard;
            $params[] = $searchWildcard;
            $params[] = $searchWildcard;
        }

        $row = Connection::selectOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }
}
