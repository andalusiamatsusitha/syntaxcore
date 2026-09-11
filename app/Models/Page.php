<?php

namespace App\Models;

use Core\Database\Model;

class Page extends Model
{
    protected ?string $table = 'pages';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'title',
        'slug',
        'content',
        'page_type',
        'layout_template',
        'comment_settings',
        'meta_title',
        'meta_description',
        'status',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    public static function findBySlug(string $slug): ?static
    {
        $cleanSlug = trim($slug, '/');
        if ($cleanSlug === '') {
            $cleanSlug = '/';
        }

        // Try exact match or without leading slash
        $results = static::where('slug', '=', $cleanSlug);
        if (!empty($results[0])) {
            return $results[0];
        }

        $results = static::where('slug', '=', '/' . $cleanSlug);
        return $results[0] ?? null;
    }

    /**
     * Dapatkan konfigurasi comment_settings dengan fallback nilai default.
     *
     * @return array{
     *     enabled: bool,
     *     style: string,
     *     allow_guests: bool,
     *     moderation: bool,
     *     per_page: int
     * }
     */
    public function getCommentSettings(): array
    {
        $defaults = [
            'enabled' => true,
            'style' => 'cards',      // 'cards', 'threaded', 'minimal'
            'allow_guests' => true,
            'moderation' => false,
            'per_page' => 20,
        ];

        if (empty($this->comment_settings)) {
            return $defaults;
        }

        $decoded = is_string($this->comment_settings)
            ? json_decode($this->comment_settings, true)
            : $this->comment_settings;

        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /**
     * Set konfigurasi comment_settings dan serialize ke JSON.
     */
    public function setCommentSettings(array $settings): void
    {
        $current = $this->getCommentSettings();
        $merged = array_merge($current, $settings);
        $this->comment_settings = json_encode($merged);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isStandard(): bool
    {
        return empty($this->page_type) || $this->page_type === 'standard';
    }

    public function isNewsIndex(): bool
    {
        return $this->page_type === 'news_index';
    }

    public function isNewsSingle(): bool
    {
        return $this->page_type === 'news_single';
    }

    public function getLayoutTemplate(): string
    {
        return !empty($this->layout_template) ? $this->layout_template : 'default';
    }
}
