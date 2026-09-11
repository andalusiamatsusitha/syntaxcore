<?php

namespace App\Models;

use Core\Database\Connection;
use Core\Database\Model;

class Comment extends Model
{
    protected ?string $table = 'comments';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'news_id',
        'parent_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at',
    ];

    public function news(): ?News
    {
        if (empty($this->news_id)) {
            return null;
        }
        return News::find($this->news_id);
    }

    public function parent(): ?self
    {
        if (empty($this->parent_id)) {
            return null;
        }
        return static::find((int) $this->parent_id);
    }

    public function user(): ?User
    {
        if (empty($this->user_id)) {
            return null;
        }
        return User::find((int) $this->user_id);
    }

    /**
     * Dapatkan komentar balasan (replies).
     *
     * @return Comment[]
     */
    public function replies(bool $onlyApproved = true): array
    {
        $sql = "SELECT * FROM `comments` WHERE `parent_id` = ?";
        $params = [$this->id];

        if ($onlyApproved) {
            $sql .= " AND `status` = 'approved'";
        }

        $sql .= " ORDER BY `created_at` ASC, `id` ASC";

        $rows = Connection::select($sql, $params, $this->connection);

        return array_map(function ($row) {
            $reply = new static();
            $reply->forceFill($row);
            $reply->original = $row;
            return $reply;
        }, $rows);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
