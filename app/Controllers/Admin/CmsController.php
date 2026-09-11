<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Comment;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicMenu;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use Core\Controller\Controller;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;

class CmsController extends Controller
{
    public function __construct(
        protected AuthService $auth = new AuthService()
    ) {
    }

    // =========================================================================
    // 1. KATEGORI (CATEGORIES)
    // =========================================================================

    public function categories(Request $request): Response
    {
        $categories = Category::all();
        $data = array_map(function (Category $cat) {
            $arr = $cat->toArray();
            $arr['news_count'] = $cat->newsCount();
            $parent = $cat->parent();
            $arr['parent_name'] = $parent?->name;
            return $arr;
        }, $categories);

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function storeCategory(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return $this->json(['status' => 'error', 'message' => 'Nama kategori wajib diisi.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', ''));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($name);

        $existing = Category::findBySlug($slug);
        if ($existing) {
            return $this->json(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan."], 422);
        }

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $color = trim((string) $request->input('color', '#0d6efd')) ?: '#0d6efd';
        $description = trim((string) $request->input('description', ''));

        $now = date('Y-m-d H:i:s');
        $cat = Category::create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLogger::log('cms.category_create', "Membuat kategori CMS: {$cat->name}", $this->auth->id(), $request);
        ActivityLogger::notify('Kategori Dibuat', "Kategori '{$cat->name}' berhasil ditambahkan.", 'success');

        return $this->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dibuat.',
            'data' => $cat->toArray(),
        ], 201);
    }

    public function updateCategory(Request $request): Response
    {
        $id = (int) $request->param('id');
        $cat = Category::find($id);
        if (!$cat) {
            return $this->json(['status' => 'error', 'message' => 'Kategori tidak ditemukan.'], 404);
        }

        $name = trim((string) $request->input('name', $cat->name));
        if ($name === '') {
            return $this->json(['status' => 'error', 'message' => 'Nama kategori tidak boleh kosong.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', $cat->slug));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($name);

        if ($slug !== $cat->slug) {
            $existing = Category::findBySlug($slug);
            if ($existing && (int) $existing->id !== $id) {
                return $this->json(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan oleh kategori lain."], 422);
            }
        }

        $parentId = $request->has('parent_id')
            ? ($request->input('parent_id') ? (int) $request->input('parent_id') : null)
            : $cat->parent_id;

        // Cegah self-parent
        if ($parentId === $id) {
            $parentId = null;
        }

        $color = trim((string) $request->input('color', $cat->color ?: '#0d6efd'));
        $description = trim((string) $request->input('description', $cat->description ?: ''));

        $cat->update([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
            'description' => $description,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log('cms.category_update', "Memperbarui kategori CMS: {$cat->name}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $cat->toArray(),
        ]);
    }

    public function deleteCategory(Request $request): Response
    {
        $id = (int) $request->param('id');
        $cat = Category::find($id);
        if (!$cat) {
            return $this->json(['status' => 'error', 'message' => 'Kategori tidak ditemukan.'], 404);
        }

        $name = $cat->name;
        $cat->delete();

        ActivityLogger::log('cms.category_delete', "Menghapus kategori CMS: {$name}", $this->auth->id(), $request);
        ActivityLogger::notify('Kategori Dihapus', "Kategori '{$name}' telah dihapus.", 'info');

        return $this->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 2. TAGS
    // =========================================================================

    public function tags(Request $request): Response
    {
        $tags = Tag::all();
        $data = array_map(function (Tag $tag) {
            $arr = $tag->toArray();
            $arr['news_count'] = $tag->newsCount();
            return $arr;
        }, $tags);

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function storeTag(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return $this->json(['status' => 'error', 'message' => 'Nama tag wajib diisi.'], 422);
        }

        $tag = Tag::firstOrCreateByName($name);

        ActivityLogger::log('cms.tag_create', "Menambahkan tag CMS: {$tag->name}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Tag berhasil dibuat atau ditemukan.',
            'data' => $tag->toArray(),
        ], 201);
    }

    public function deleteTag(Request $request): Response
    {
        $id = (int) $request->param('id');
        $tag = Tag::find($id);
        if (!$tag) {
            return $this->json(['status' => 'error', 'message' => 'Tag tidak ditemukan.'], 404);
        }

        $name = $tag->name;
        $tag->delete();

        ActivityLogger::log('cms.tag_delete', "Menghapus tag CMS: {$name}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Tag berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 3. BERITA & ARTIKEL (NEWS)
    // =========================================================================

    public function news(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(100, (int) $request->query('limit', 15)));
        $offset = ($page - 1) * $limit;

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;
        $status = trim((string) $request->query('status', ''));

        $sql = "SELECT n.*, c.name as category_name, c.color as category_color, u.name as author_name
                FROM `news` n
                LEFT JOIN `categories` c ON n.category_id = c.id
                LEFT JOIN `users` u ON n.user_id = u.id
                WHERE 1=1";
        $countSql = "SELECT COUNT(*) as cnt FROM `news` n WHERE 1=1";
        $params = [];
        $countParams = [];

        if ($search !== '') {
            $clause = " AND (n.title LIKE ? OR n.summary LIKE ?)";
            $wildcard = '%' . $search . '%';
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $countParams[] = $wildcard;
            $countParams[] = $wildcard;
        }

        if ($categoryId !== null) {
            $clause = " AND n.category_id = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $categoryId;
            $countParams[] = $categoryId;
        }

        if ($status !== '' && in_array($status, ['published', 'draft', 'archived'], true)) {
            $clause = " AND n.status = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $status;
            $countParams[] = $status;
        }

        $sql .= " ORDER BY n.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $rows = Connection::select($sql, $params);
        $totalRow = Connection::selectOne($countSql, $countParams);
        $total = (int) ($totalRow['cnt'] ?? 0);

        $items = array_map(function ($row) {
            $n = new News();
            $n->forceFill($row);
            $n->original = $row;
            $data = $n->toArray();
            $data['category_name'] = $row['category_name'] ?? 'Tanpa Kategori';
            $data['category_color'] = $row['category_color'] ?? '#6c757d';
            $data['author_name'] = $row['author_name'] ?? 'Admin';
            $data['tags'] = array_map(fn(Tag $t) => $t->toArray(), $n->tags());
            $data['comments_count'] = $n->approvedCommentsCount();
            return $data;
        }, $rows);

        return $this->json([
            'status' => 'success',
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit),
            ],
        ]);
    }

    public function showNews(Request $request): Response
    {
        $id = (int) $request->param('id');
        $news = News::find($id);
        if (!$news) {
            return $this->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404);
        }

        $data = $news->toArray();
        $data['category'] = $news->category()?->toArray();
        $data['author'] = $news->author()?->toArray();
        $data['tags'] = array_map(fn(Tag $t) => $t->toArray(), $news->tags());
        $data['comments_count'] = $news->approvedCommentsCount();

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function storeNews(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));
        $content = trim((string) $request->input('content', ''));

        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul berita wajib diisi.'], 422);
        }
        if ($content === '') {
            return $this->json(['status' => 'error', 'message' => 'Konten berita wajib diisi.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', ''));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        $existing = News::findBySlug($slug);
        if ($existing) {
            $slug = $slug . '-' . time();
        }

        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $summary = trim((string) $request->input('summary', ''));
        if ($summary === '') {
            // Auto generate summary dari paragraf pertama konten
            $cleanText = strip_tags($content);
            $summary = mb_substr($cleanText, 0, 180) . (mb_strlen($cleanText) > 180 ? '...' : '');
        }

        $featuredImage = trim((string) $request->input('featured_image', '')) ?: null;
        $allowComments = $request->has('allow_comments') ? (int) (bool) $request->input('allow_comments') : 1;
        $status = in_array($request->input('status'), ['published', 'draft', 'archived'], true)
            ? $request->input('status')
            : 'published';

        $publishedAt = $request->input('published_at');
        if (empty($publishedAt) && $status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $now = date('Y-m-d H:i:s');
        $news = News::create([
            'category_id' => $categoryId,
            'user_id' => $this->auth->id(),
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'content' => $content,
            'featured_image' => $featuredImage,
            'allow_comments' => $allowComments,
            'views_count' => 0,
            'status' => $status,
            'published_at' => $publishedAt ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Tangani tags
        $tagIds = $this->resolveTagIds($request->input('tags'));
        $news->syncTags($tagIds);

        ActivityLogger::log('cms.news_create', "Menerbitkan berita baru: {$news->title}", $this->auth->id(), $request);
        ActivityLogger::notify('Berita Diterbitkan', "Berita '{$news->title}' berhasil disimpan.", 'success');

        return $this->json([
            'status' => 'success',
            'message' => 'Berita berhasil diterbitkan.',
            'data' => $news->toArray(),
        ], 201);
    }

    public function updateNews(Request $request): Response
    {
        $id = (int) $request->param('id');
        $news = News::find($id);
        if (!$news) {
            return $this->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404);
        }

        $title = trim((string) $request->input('title', $news->title));
        $content = trim((string) $request->input('content', $news->content));

        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul berita tidak boleh kosong.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', $news->slug));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        if ($slug !== $news->slug) {
            $existing = News::findBySlug($slug);
            if ($existing && (int) $existing->id !== $id) {
                $slug = $slug . '-' . time();
            }
        }

        $categoryId = $request->has('category_id')
            ? ($request->input('category_id') ? (int) $request->input('category_id') : null)
            : $news->category_id;

        $summary = $request->has('summary') ? trim((string) $request->input('summary')) : $news->summary;
        $featuredImage = $request->has('featured_image') ? trim((string) $request->input('featured_image')) : $news->featured_image;
        $allowComments = $request->has('allow_comments') ? (int) (bool) $request->input('allow_comments') : (int) $news->allow_comments;

        $status = $news->status;
        if ($request->has('status') && in_array($request->input('status'), ['published', 'draft', 'archived'], true)) {
            $status = $request->input('status');
        }

        $publishedAt = $news->published_at;
        if ($request->has('published_at')) {
            $publishedAt = $request->input('published_at') ?: null;
        } elseif ($status === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $news->update([
            'category_id' => $categoryId,
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'content' => $content,
            'featured_image' => $featuredImage ?: null,
            'allow_comments' => $allowComments,
            'status' => $status,
            'published_at' => $publishedAt,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($request->has('tags')) {
            $tagIds = $this->resolveTagIds($request->input('tags'));
            $news->syncTags($tagIds);
        }

        ActivityLogger::log('cms.news_update', "Memperbarui berita: {$news->title}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Berita berhasil diperbarui.',
            'data' => $news->toArray(),
        ]);
    }

    public function deleteNews(Request $request): Response
    {
        $id = (int) $request->param('id');
        $news = News::find($id);
        if (!$news) {
            return $this->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404);
        }

        $title = $news->title;
        $news->delete();

        ActivityLogger::log('cms.news_delete', "Menghapus berita: {$title}", $this->auth->id(), $request);
        ActivityLogger::notify('Berita Dihapus', "Berita '{$title}' berhasil dihapus.", 'info');

        return $this->json([
            'status' => 'success',
            'message' => 'Berita berhasil dihapus.',
        ]);
    }

    public function uploadNewsImage(Request $request): Response
    {
        $files = $request->files();
        $file = $files['image'] ?? null;

        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return $this->json(['status' => 'error', 'message' => 'Berkas gambar wajib diunggah.'], 422);
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return $this->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengunggah berkas.'], 422);
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $origName = $file['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            return $this->json(['status' => 'error', 'message' => 'Format gambar tidak didukung (gunakan JPG, PNG, WEBP, GIF).'], 422);
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mime, $allowedMimes, true)) {
                return $this->json(['status' => 'error', 'message' => 'Berkas bukan gambar yang valid.'], 422);
            }
        }

        $targetDir = dirname(__DIR__, 3) . '/public/uploads/news';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        $moved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $targetPath)
            : copy($file['tmp_name'], $targetPath);

        if (!$moved) {
            return $this->json(['status' => 'error', 'message' => 'Gagal menyimpan gambar ke disk.'], 500);
        }

        $url = '/uploads/news/' . $filename;

        return $this->json([
            'status' => 'success',
            'message' => 'Gambar berhasil diunggah.',
            'url' => $url,
        ]);
    }

    // =========================================================================
    // 4. HALAMAN (PAGES)
    // =========================================================================

    public function pages(Request $request): Response
    {
        $pages = Page::all();
        $data = array_map(function (Page $p) {
            $arr = $p->toArray();
            $arr['comment_settings'] = $p->getCommentSettings();
            return $arr;
        }, $pages);

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function showPage(Request $request): Response
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            return $this->json(['status' => 'error', 'message' => 'Halaman tidak ditemukan.'], 404);
        }

        $data = $page->toArray();
        $data['comment_settings'] = $page->getCommentSettings();

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function storePage(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul halaman wajib diisi.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', ''));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        $existing = Page::findBySlug($slug);
        if ($existing) {
            return $this->json(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan oleh halaman lain."], 422);
        }

        $pageType = in_array($request->input('page_type'), ['standard', 'news_index', 'news_single'], true)
            ? $request->input('page_type')
            : 'standard';

        $layoutTemplate = in_array($request->input('layout_template'), ['default', 'fullwidth', 'sidebar', 'blank'], true)
            ? $request->input('layout_template')
            : 'default';

        $commentSettings = $request->input('comment_settings');
        if (is_string($commentSettings)) {
            $commentSettings = json_decode($commentSettings, true) ?: [];
        }
        if (!is_array($commentSettings)) {
            $commentSettings = [
                'enabled' => $pageType === 'news_single',
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 20,
            ];
        }

        $status = in_array($request->input('status'), ['published', 'draft'], true)
            ? $request->input('status')
            : 'published';

        $now = date('Y-m-d H:i:s');
        $page = Page::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $request->input('content', ''),
            'page_type' => $pageType,
            'layout_template' => $layoutTemplate,
            'comment_settings' => json_encode($commentSettings),
            'meta_title' => trim((string) $request->input('meta_title', '')) ?: null,
            'meta_description' => trim((string) $request->input('meta_description', '')) ?: null,
            'status' => $status,
            'sort_order' => (int) $request->input('sort_order', 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLogger::log('cms.page_create', "Membuat halaman CMS: {$page->title}", $this->auth->id(), $request);
        ActivityLogger::notify('Halaman Dibuat', "Halaman '{$page->title}' berhasil dibuat.", 'success');

        return $this->json([
            'status' => 'success',
            'message' => 'Halaman berhasil dibuat.',
            'data' => $page->toArray(),
        ], 201);
    }

    public function updatePage(Request $request): Response
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            return $this->json(['status' => 'error', 'message' => 'Halaman tidak ditemukan.'], 404);
        }

        $title = trim((string) $request->input('title', $page->title));
        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul halaman tidak boleh kosong.'], 422);
        }

        $slugInput = trim((string) $request->input('slug', $page->slug));
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        if ($slug !== $page->slug) {
            $existing = Page::findBySlug($slug);
            if ($existing && (int) $existing->id !== $id) {
                return $this->json(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan."], 422);
            }
        }

        $pageType = $page->page_type;
        if ($request->has('page_type') && in_array($request->input('page_type'), ['standard', 'news_index', 'news_single'], true)) {
            $pageType = $request->input('page_type');
        }

        $layoutTemplate = $page->layout_template ?? 'default';
        if ($request->has('layout_template') && in_array($request->input('layout_template'), ['default', 'fullwidth', 'sidebar', 'blank'], true)) {
            $layoutTemplate = $request->input('layout_template');
        }

        $commentSettings = $page->getCommentSettings();
        if ($request->has('comment_settings')) {
            $inputSettings = $request->input('comment_settings');
            if (is_string($inputSettings)) {
                $inputSettings = json_decode($inputSettings, true) ?: [];
            }
            if (is_array($inputSettings)) {
                $commentSettings = array_merge($commentSettings, $inputSettings);
            }
        }

        $status = $page->status;
        if ($request->has('status') && in_array($request->input('status'), ['published', 'draft'], true)) {
            $status = $request->input('status');
        }

        $page->update([
            'title' => $title,
            'slug' => $slug,
            'content' => $request->has('content') ? $request->input('content') : $page->content,
            'page_type' => $pageType,
            'layout_template' => $layoutTemplate,
            'comment_settings' => json_encode($commentSettings),
            'meta_title' => $request->has('meta_title') ? (trim((string) $request->input('meta_title')) ?: null) : $page->meta_title,
            'meta_description' => $request->has('meta_description') ? (trim((string) $request->input('meta_description')) ?: null) : $page->meta_description,
            'status' => $status,
            'sort_order' => $request->has('sort_order') ? (int) $request->input('sort_order') : (int) $page->sort_order,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log('cms.page_update', "Memperbarui halaman CMS: {$page->title}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Halaman berhasil diperbarui.',
            'data' => $page->toArray(),
        ]);
    }

    public function deletePage(Request $request): Response
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            return $this->json(['status' => 'error', 'message' => 'Halaman tidak ditemukan.'], 404);
        }

        $title = $page->title;
        $page->delete();

        ActivityLogger::log('cms.page_delete', "Menghapus halaman CMS: {$title}", $this->auth->id(), $request);
        ActivityLogger::notify('Halaman Dihapus', "Halaman '{$title}' berhasil dihapus.", 'info');

        return $this->json([
            'status' => 'success',
            'message' => 'Halaman berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 5. KOMENTAR & MODERASI (COMMENTS)
    // =========================================================================

    public function comments(Request $request): Response
    {
        $status = trim((string) $request->query('status', ''));
        $newsId = $request->query('news_id') ? (int) $request->query('news_id') : null;

        $sql = "SELECT c.*, n.title as news_title, n.slug as news_slug
                FROM `comments` c
                INNER JOIN `news` n ON c.news_id = n.id
                WHERE 1=1";
        $params = [];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'spam', 'rejected'], true)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        if ($newsId !== null) {
            $sql .= " AND c.news_id = ?";
            $params[] = $newsId;
        }

        $sql .= " ORDER BY c.id DESC";

        $rows = Connection::select($sql, $params);

        return $this->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function updateCommentStatus(Request $request): Response
    {
        $id = (int) $request->param('id');
        $comment = Comment::find($id);
        if (!$comment) {
            return $this->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan.'], 404);
        }

        $newStatus = trim((string) $request->input('status', ''));
        if (!in_array($newStatus, ['approved', 'pending', 'spam', 'rejected'], true)) {
            return $this->json(['status' => 'error', 'message' => 'Status komentar tidak valid.'], 422);
        }

        $comment->update([
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log('cms.comment_status', "Mengubah status komentar #{$id} menjadi '{$newStatus}'", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => "Status komentar berhasil diubah menjadi {$newStatus}.",
            'data' => $comment->toArray(),
        ]);
    }

    public function replyComment(Request $request): Response
    {
        $id = (int) $request->param('id');
        $parent = Comment::find($id);
        if (!$parent) {
            return $this->json(['status' => 'error', 'message' => 'Komentar induk tidak ditemukan.'], 404);
        }

        $content = trim((string) $request->input('content', ''));
        if ($content === '') {
            return $this->json(['status' => 'error', 'message' => 'Isi balasan komentar wajib diisi.'], 422);
        }

        $user = $this->auth->user();
        $authorName = $user?->name ?: 'Administrator';
        $authorEmail = $user?->email ?: 'admin@syntaxcore.com';

        $now = date('Y-m-d H:i:s');
        $reply = Comment::create([
            'news_id' => $parent->news_id,
            'parent_id' => $parent->id,
            'user_id' => $this->auth->id(),
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $content,
            'status' => 'approved',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('USER_AGENT', 'SyntaxCore Admin Desk'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLogger::log('cms.comment_reply', "Membalas komentar #{$parent->id} pada berita ID: {$parent->news_id}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Balasan komentar berhasil dikirim.',
            'data' => $reply->toArray(),
        ], 201);
    }

    public function deleteComment(Request $request): Response
    {
        $id = (int) $request->param('id');
        $comment = Comment::find($id);
        if (!$comment) {
            return $this->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan.'], 404);
        }

        $comment->delete();

        ActivityLogger::log('cms.comment_delete', "Menghapus komentar #{$id}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 6. NAVIGASI MENU PUBLIK (PUBLIC MENUS)
    // =========================================================================

    public function publicMenus(Request $request): Response
    {
        $tree = PublicMenu::tree(false);
        $flat = PublicMenu::all();

        return $this->json([
            'status' => 'success',
            'tree' => $tree,
            'items' => array_map(function (PublicMenu $m) {
                $arr = $m->toArray();
                $arr['computed_url'] = $m->getComputedUrl();
                return $arr;
            }, $flat),
        ]);
    }

    public function storePublicMenu(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul menu wajib diisi.'], 422);
        }

        $linkType = in_array($request->input('link_type'), ['page', 'news_category', 'news_tag', 'news_index', 'custom_url'], true)
            ? $request->input('link_type')
            : 'page';

        $linkTarget = trim((string) $request->input('link_target', '')) ?: null;
        $url = trim((string) $request->input('url', '')) ?: null;
        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $sortOrder = (int) $request->input('sort_order', 0);
        $isActive = $request->has('is_active') ? (int) (bool) $request->input('is_active') : 1;

        $now = date('Y-m-d H:i:s');
        $menu = PublicMenu::create([
            'parent_id' => $parentId,
            'title' => $title,
            'link_type' => $linkType,
            'link_target' => $linkTarget,
            'url' => $url,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLogger::log('cms.menu_create', "Menambahkan menu publik: {$menu->title}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Menu publik berhasil dibuat.',
            'data' => $menu->toArray(),
        ], 201);
    }

    public function updatePublicMenu(Request $request): Response
    {
        $id = (int) $request->param('id');
        $menu = PublicMenu::find($id);
        if (!$menu) {
            return $this->json(['status' => 'error', 'message' => 'Menu tidak ditemukan.'], 404);
        }

        $title = trim((string) $request->input('title', $menu->title));
        if ($title === '') {
            return $this->json(['status' => 'error', 'message' => 'Judul menu tidak boleh kosong.'], 422);
        }

        $linkType = $menu->link_type;
        if ($request->has('link_type') && in_array($request->input('link_type'), ['page', 'news_category', 'news_tag', 'news_index', 'custom_url'], true)) {
            $linkType = $request->input('link_type');
        }

        $parentId = $request->has('parent_id')
            ? ($request->input('parent_id') ? (int) $request->input('parent_id') : null)
            : $menu->parent_id;

        if ($parentId === $id) {
            $parentId = null;
        }

        $menu->update([
            'parent_id' => $parentId,
            'title' => $title,
            'link_type' => $linkType,
            'link_target' => $request->has('link_target') ? (trim((string) $request->input('link_target')) ?: null) : $menu->link_target,
            'url' => $request->has('url') ? (trim((string) $request->input('url')) ?: null) : $menu->url,
            'sort_order' => $request->has('sort_order') ? (int) $request->input('sort_order') : (int) $menu->sort_order,
            'is_active' => $request->has('is_active') ? (int) (bool) $request->input('is_active') : (int) $menu->is_active,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLogger::log('cms.menu_update', "Memperbarui menu publik: {$menu->title}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Menu publik berhasil diperbarui.',
            'data' => $menu->toArray(),
        ]);
    }

    public function deletePublicMenu(Request $request): Response
    {
        $id = (int) $request->param('id');
        $menu = PublicMenu::find($id);
        if (!$menu) {
            return $this->json(['status' => 'error', 'message' => 'Menu tidak ditemukan.'], 404);
        }

        $title = $menu->title;
        $menu->delete();

        ActivityLogger::log('cms.menu_delete', "Menghapus menu publik: {$title}", $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Menu publik berhasil dihapus.',
        ]);
    }

    public function reorderPublicMenus(Request $request): Response
    {
        $items = $request->input('items', []);
        if (!is_array($items)) {
            return $this->json(['status' => 'error', 'message' => 'Data urutan tidak valid.'], 422);
        }

        foreach ($items as $item) {
            if (isset($item['id'])) {
                $id = (int) $item['id'];
                $sort = (int) ($item['sort_order'] ?? 0);
                $parentId = isset($item['parent_id']) && $item['parent_id'] ? (int) $item['parent_id'] : null;

                Connection::statement(
                    "UPDATE `public_menus` SET `sort_order` = ?, `parent_id` = ?, `updated_at` = NOW() WHERE `id` = ?",
                    [$sort, $parentId, $id]
                );
            }
        }

        ActivityLogger::log('cms.menu_reorder', 'Menyusun ulang urutan navigasi menu publik', $this->auth->id(), $request);

        return $this->json([
            'status' => 'success',
            'message' => 'Urutan menu publik berhasil disimpan.',
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'item-' . time() : $text;
    }

    /**
     * Mengubah array of ID atau string nama tag menjadi array ID integer.
     */
    private function resolveTagIds(mixed $tagsInput): array
    {
        if (empty($tagsInput)) {
            return [];
        }

        if (is_string($tagsInput)) {
            $tagsInput = explode(',', $tagsInput);
        }

        if (!is_array($tagsInput)) {
            return [];
        }

        $tagIds = [];
        foreach ($tagsInput as $item) {
            if (is_numeric($item)) {
                $tagIds[] = (int) $item;
            } elseif (is_string($item) && trim($item) !== '') {
                $tag = Tag::firstOrCreateByName(trim($item));
                $tagIds[] = (int) $tag->id;
            }
        }

        return array_unique($tagIds);
    }
}
