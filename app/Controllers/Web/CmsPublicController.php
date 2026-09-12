<?php

namespace App\Controllers\Web;

use App\Models\Category;
use App\Models\Comment;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicMenu;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use Core\Controller\Controller;
use Core\Exceptions\HttpException;
use Core\Http\Request;
use Core\Http\Response;

class CmsPublicController extends Controller
{
    public function __construct(
        protected AuthService $auth = new AuthService()
    ) {
    }

    /**
     * Tampilkan Halaman Beranda (Home /)
     */
    public function home(Request $request): Response
    {
        $page = Page::findBySlug('beranda');
        $menus = PublicMenu::tree(true);
        $recentNews = News::getPublishedNews(limit: 3);
        $categories = Category::all();

        return $this->view('web.home.index', [
            'appName' => 'SyntaxCore',
            'version' => '1.0.0',
            'phpVersion' => PHP_VERSION,
            'page' => $page,
            'menus' => $menus,
            'recentNews' => $recentNews,
            'categories' => $categories,
            'activeMenu' => '/',
        ]);
    }

    /**
     * Resolusi Halaman Dinamis dari Database berdasarkan Slug (Catch-All)
     */
    public function page(Request $request): Response
    {
        $slug = trim((string) $request->param('slug', ''));
        if ($slug === '' || $slug === 'beranda') {
            return $this->home($request);
        }

        $page = Page::findBySlug($slug);
        if (!$page || !$page->isPublished()) {
            throw new HttpException(404, "Halaman '{$slug}' tidak ditemukan.");
        }

        if ($page->isNewsIndex()) {
            return $this->newsIndex($request, $page);
        }

        if ($page->isNewsSingle()) {
            return $this->newsSingleReader($request, $page);
        }

        // Standard Page with dynamic layout template
        $menus = PublicMenu::tree(true);
        $layoutTemplate = $page->getLayoutTemplate();

        $viewName = match ($layoutTemplate) {
            'fullwidth' => 'web.pages.fullwidth',
            'sidebar' => 'web.pages.sidebar',
            'blank' => 'web.pages.blank',
            default => 'web.pages.standard',
        };

        $sidebarPages = [];
        $recentNews = [];
        if ($layoutTemplate === 'sidebar') {
            $sidebarPages = Page::where('status', '=', 'published');
            $recentNews = News::getPublishedNews(limit: 5);
        }

        return $this->view($viewName, [
            'page' => $page,
            'menus' => $menus,
            'activeMenu' => '/' . $page->slug,
            'sidebarPages' => $sidebarPages,
            'recentNews' => $recentNews,
        ]);
    }

    /**
     * Halaman Arsip / Daftar Feed Berita
     */
    public function newsIndex(Request $request, ?Page $page = null): Response
    {
        $pageModel = $page ?: Page::findBySlug('berita');
        $menus = PublicMenu::tree(true);
        $categories = Category::all();
        $tags = Tag::all();

        $search = trim((string) $request->query('search', ''));
        $catSlug = trim((string) $request->query('kategori', ''));
        $tagSlug = trim((string) $request->query('tag', ''));

        $categoryId = null;
        $activeCategory = null;
        if ($catSlug !== '') {
            $activeCategory = Category::findBySlug($catSlug);
            $categoryId = $activeCategory?->id;
        }

        $tagId = null;
        $activeTag = null;
        if ($tagSlug !== '') {
            $activeTag = Tag::findBySlug($tagSlug);
            $tagId = $activeTag?->id;
        }

        $currentPage = max(1, (int) $request->query('page', 1));
        $perPage = 6;
        $offset = ($currentPage - 1) * $perPage;

        $newsList = News::getPublishedNews(
            limit: $perPage,
            offset: $offset,
            categoryId: $categoryId,
            tagId: $tagId,
            search: $search !== '' ? $search : null
        );

        $totalNews = News::countPublishedNews(
            categoryId: $categoryId,
            tagId: $tagId,
            search: $search !== '' ? $search : null
        );

        $totalPages = max(1, (int) ceil($totalNews / $perPage));

        return $this->view('web.pages.news_index', [
            'page' => $pageModel,
            'menus' => $menus,
            'newsList' => $newsList,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
            'search' => $search,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalNews' => $totalNews,
            'activeMenu' => '/berita',
        ]);
    }

    /**
     * Filter Berita berdasarkan Kategori
     */
    public function newsCategory(Request $request): Response
    {
        $slug = trim((string) $request->param('slug', ''));
        $cat = Category::findBySlug($slug);
        if (!$cat) {
            throw new HttpException(404, "Kategori '{$slug}' tidak ditemukan.");
        }

        $menus = PublicMenu::tree(true);
        $categories = Category::all();
        $tags = Tag::all();

        $currentPage = max(1, (int) $request->query('page', 1));
        $perPage = 6;
        $offset = ($currentPage - 1) * $perPage;

        $newsList = News::getPublishedNews(limit: $perPage, offset: $offset, categoryId: $cat->id);
        $totalNews = News::countPublishedNews(categoryId: $cat->id);
        $totalPages = max(1, (int) ceil($totalNews / $perPage));

        return $this->view('web.pages.news_index', [
            'page' => Page::findBySlug('berita'),
            'menus' => $menus,
            'newsList' => $newsList,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $cat,
            'activeTag' => null,
            'search' => '',
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalNews' => $totalNews,
            'activeMenu' => '/berita',
        ]);
    }

    /**
     * Filter Berita berdasarkan Tag
     */
    public function newsTag(Request $request): Response
    {
        $slug = trim((string) $request->param('slug', ''));
        $tag = Tag::findBySlug($slug);
        if (!$tag) {
            throw new HttpException(404, "Tag '{$slug}' tidak ditemukan.");
        }

        $menus = PublicMenu::tree(true);
        $categories = Category::all();
        $tags = Tag::all();

        $currentPage = max(1, (int) $request->query('page', 1));
        $perPage = 6;
        $offset = ($currentPage - 1) * $perPage;

        $newsList = News::getPublishedNews(limit: $perPage, offset: $offset, tagId: $tag->id);
        $totalNews = News::countPublishedNews(tagId: $tag->id);
        $totalPages = max(1, (int) ceil($totalNews / $perPage));

        return $this->view('web.pages.news_index', [
            'page' => Page::findBySlug('berita'),
            'menus' => $menus,
            'newsList' => $newsList,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => null,
            'activeTag' => $tag,
            'search' => '',
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalNews' => $totalNews,
            'activeMenu' => '/berita',
        ]);
    }

    /**
     * Pembaca Detail Berita Tunggal (/berita/{slug})
     */
    public function newsSingle(Request $request): Response
    {
        $slug = trim((string) $request->param('slug', ''));
        $news = News::findBySlug($slug);

        if (!$news || $news->status !== 'published') {
            throw new HttpException(404, "Artikel berita '{$slug}' tidak ditemukan.");
        }

        $news->incrementViews();

        // Cari konfigurasi halaman reader (default: baca-berita)
        $readerPage = Page::findBySlug('baca-berita') ?? new Page([
            'title' => 'Detail Berita',
            'slug' => 'baca-berita',
            'page_type' => 'news_single',
            'comment_settings' => json_encode([
                'enabled' => true,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 20,
            ]),
        ]);

        $commentSettings = $readerPage->getCommentSettings();
        $comments = $news->comments(onlyApproved: true);
        $menus = PublicMenu::tree(true);
        $user = $this->auth->user();

        return $this->view('web.pages.news_single', [
            'news' => $news,
            'page' => $readerPage,
            'menus' => $menus,
            'commentSettings' => $commentSettings,
            'comments' => $comments,
            'user' => $user,
            'activeMenu' => '/berita',
        ]);
    }

    /**
     * Fallback URL reader via query param: /baca-berita?slug=...
     */
    public function newsSingleReader(Request $request, ?Page $page = null): Response
    {
        $slug = trim((string) $request->query('slug', ''));
        if ($slug !== '') {
            $request->setParams(array_merge($request->params(), ['slug' => $slug]));
            return $this->newsSingle($request);
        }

        // Jika tidak ada slug berita, arahkan ke indeks berita
        header('Location: /berita', true, 302);
        exit;
    }

    /**
     * Submit Komentar Publik pada Berita (POST /news/{id}/comments)
     */
    public function submitComment(Request $request): Response
    {
        // 1. Anti-spam honeypot check: field website_hp harus kosong
        $honeypot = trim((string) $request->input('website_hp', ''));
        if ($honeypot !== '') {
            // Diduga spam bot, tolak tanpa proses
            if ($request->wantsJson()) {
                return $this->json(['status' => 'error', 'message' => 'Spam terdeteksi.'], 422);
            }
            header('Location: /berita', true, 302);
            exit;
        }

        $newsId = (int) ($request->param('id') ?: $request->input('news_id'));
        $news = News::find($newsId);
        if (!$news || $news->status !== 'published') {
            throw new HttpException(404, 'Berita tidak ditemukan.');
        }

        if (empty($news->allow_comments)) {
            if ($request->wantsJson()) {
                return $this->json(['status' => 'error', 'message' => 'Kolom komentar pada berita ini telah dinonaktifkan.'], 403);
            }
            $_SESSION['comment_flash'] = ['type' => 'danger', 'message' => 'Kolom komentar pada berita ini telah dinonaktifkan.'];
            header('Location: /berita/' . $news->slug . '#comments', true, 302);
            exit;
        }

        // Ambil konfigurasi halaman pembaca
        $readerPage = Page::findBySlug('baca-berita');
        $settings = $readerPage ? $readerPage->getCommentSettings() : [
            'enabled' => true,
            'allow_guests' => true,
            'moderation' => false,
        ];

        if (empty($settings['enabled'])) {
            if ($request->wantsJson()) {
                return $this->json(['status' => 'error', 'message' => 'Interaksi komentar dinonaktifkan.'], 403);
            }
            $_SESSION['comment_flash'] = ['type' => 'danger', 'message' => 'Interaksi komentar dinonaktifkan.'];
            header('Location: /berita/' . $news->slug . '#comments', true, 302);
            exit;
        }

        $user = $this->auth->user();
        $userId = $user?->id;
        $authorName = '';
        $authorEmail = '';

        if ($user) {
            $authorName = $user->name;
            $authorEmail = $user->email;
        } else {
            if (empty($settings['allow_guests'])) {
                if ($request->wantsJson()) {
                    return $this->json(['status' => 'error', 'message' => 'Anda harus login untuk mengirim komentar.'], 403);
                }
                $_SESSION['comment_flash'] = ['type' => 'warning', 'message' => 'Anda harus masuk / login terlebih dahulu untuk dapat mengirim komentar.'];
                header('Location: /berita/' . $news->slug . '#comments', true, 302);
                exit;
            }

            $authorName = trim((string) $request->input('author_name', ''));
            $authorEmail = trim((string) $request->input('author_email', ''));

            if ($authorName === '' || $authorEmail === '') {
                if ($request->wantsJson()) {
                    return $this->json(['status' => 'error', 'message' => 'Nama dan Email wajib diisi.'], 422);
                }
                $_SESSION['comment_flash'] = ['type' => 'danger', 'message' => 'Nama lengkap dan email wajib diisi.'];
                header('Location: /berita/' . $news->slug . '#comments', true, 302);
                exit;
            }

            if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
                if ($request->wantsJson()) {
                    return $this->json(['status' => 'error', 'message' => 'Format email tidak valid.'], 422);
                }
                $_SESSION['comment_flash'] = ['type' => 'danger', 'message' => 'Format email yang Anda masukkan tidak valid.'];
                header('Location: /berita/' . $news->slug . '#comments', true, 302);
                exit;
            }
        }

        $content = trim((string) $request->input('content', ''));
        if ($content === '' || mb_strlen($content) < 3) {
            if ($request->wantsJson()) {
                return $this->json(['status' => 'error', 'message' => 'Isi komentar minimal 3 karakter.'], 422);
            }
            $_SESSION['comment_flash'] = ['type' => 'danger', 'message' => 'Isi komentar minimal 3 karakter.'];
            header('Location: /berita/' . $news->slug . '#comments', true, 302);
            exit;
        }

        // Sanitasi teks komentar terhadap script injection
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        if ($parentId) {
            $parent = Comment::find($parentId);
            if (!$parent || (int) $parent->news_id !== $news->id) {
                $parentId = null;
            }
        }

        // Status awal komentar: jika moderation aktif -> pending, jika tidak -> approved
        $status = (!empty($settings['moderation']) && !$user?->hasRoleLevel(2)) ? 'pending' : 'approved';

        $now = date('Y-m-d H:i:s');
        $comment = Comment::create([
            'news_id' => $news->id,
            'parent_id' => $parentId,
            'user_id' => $userId,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $content,
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('USER_AGENT', 'Public Browser'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLogger::notify(
            'Komentar Masuk',
            "{$authorName} mengomentari '{$news->title}': " . mb_substr($content, 0, 80),
            $status === 'pending' ? 'warning' : 'info'
        );

        $flashMsg = $status === 'approved'
            ? 'Terima kasih! Komentar Anda berhasil dipublikasikan.'
            : 'Terima kasih! Komentar Anda telah terkirim dan sedang menunggu persetujuan moderator.';

        if ($request->wantsJson()) {
            return $this->json([
                'status' => 'success',
                'message' => $flashMsg,
                'data' => $comment->toArray(),
            ], 201);
        }

        $_SESSION['comment_flash'] = ['type' => 'success', 'message' => $flashMsg];
        header('Location: /berita/' . $news->slug . '#comments', true, 302);
        exit;
    }

    /**
     * Generate XML Sitemap untuk Search Engine (Google, Bing)
     */
    public function sitemap(Request $request): Response
    {
        $baseUrl = 'https://pkbmssupriadi.sch.id';
        $pages = Page::where('status', '=', 'published');
        $newsItems = News::where('status', '=', 'published');
        $categories = Category::all();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Beranda
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$baseUrl}/</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        // Berita Index
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$baseUrl}/berita</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>0.9</priority>\n";
        $xml .= "  </url>\n";

        // Halaman Statis / Profil
        foreach ($pages as $p) {
            if ($p->slug === 'beranda') {
                continue;
            }
            $slug = htmlspecialchars($p->slug, ENT_XML1);
            $lastmod = !empty($p->updated_at) ? date('Y-m-d', strtotime($p->updated_at)) : date('Y-m-d');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/page/{$slug}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        // Berita & Pengumuman
        foreach ($newsItems as $n) {
            $slug = htmlspecialchars($n->slug, ENT_XML1);
            $lastmod = !empty($n->updated_at) ? date('Y-m-d', strtotime($n->updated_at)) : date('Y-m-d');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/berita/{$slug}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
        }

        // Kategori Berita
        foreach ($categories as $c) {
            $slug = htmlspecialchars($c->slug, ENT_XML1);
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/berita/kategori/{$slug}</loc>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

