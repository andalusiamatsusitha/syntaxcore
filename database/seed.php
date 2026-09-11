<?php

/**
 * SyntaxCore Database Seeder
 * Run via CLI: php database/seed.php
 * 
 * Supports environment variables:
 * - SEED_ADMIN_EMAIL: Administrator email address
 * - SEED_ADMIN_PASSWORD: Administrator password
 * - SEED_ADMIN_NAME: Administrator display name
 */

$baseDir = dirname(__DIR__);

if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

$app = require_once $baseDir . '/bootstrap/app.php';

use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Models\News;
use App\Models\Page;
use App\Models\Comment;
use App\Models\PublicMenu;

echo "--- SyntaxCore Database Seeder ---\n";

try {
    // 1. Seed Roles
    $defaultRoles = [
        [
            'name' => 'Super Administrator',
            'slug' => 'superadmin',
            'level' => 3,
            'description' => 'Akses penuh ke semua modul sistem dan konfigurasi inti',
        ],
        [
            'name' => 'Administrator',
            'slug' => 'admin',
            'level' => 2,
            'description' => 'Akses manajerial pengelolaan pengguna dan operasional',
        ],
        [
            'name' => 'Regular User',
            'slug' => 'user',
            'level' => 1,
            'description' => 'Akses standar pengguna',
        ],
    ];

    $rolesMap = [];
    foreach ($defaultRoles as $roleData) {
        $role = Role::findBySlug($roleData['slug']);
        if (!$role) {
            $role = Role::create([
                'name' => $roleData['name'],
                'slug' => $roleData['slug'],
                'level' => $roleData['level'],
                'description' => $roleData['description'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "[SUCCESS] Role seeded: {$roleData['name']} ({$roleData['slug']})\n";
        } else {
            echo "[INFO] Role '{$roleData['slug']}' already exists.\n";
        }
        $rolesMap[$roleData['slug']] = $role;
    }

    // 2. Seed Default Administrator User
    $email = getenv('SEED_ADMIN_EMAIL') ?: 'admin@syntaxcore.com';
    $password = getenv('SEED_ADMIN_PASSWORD') ?: 'admin123';
    $name = getenv('SEED_ADMIN_NAME') ?: 'Administrator';
    $superadminRole = $rolesMap['superadmin'] ?? null;

    $user = User::findByEmail($email);

    if (!$user) {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'role_id' => $superadminRole?->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword($password);
        $user->save();

        echo "[SUCCESS] Administrator seeded successfully!\n";
        echo "  Name    : {$name}\n";
        echo "  Email   : {$email}\n";
        echo "  Role    : Super Administrator (superadmin)\n";
        echo "  Password: [PROTECTED / CONFIGURABLE VIA SEED_ADMIN_PASSWORD]\n";
    } else {
        // Jika user sudah ada tetapi belum punya role_id, assign role superadmin
        if (empty($user->role_id) && $superadminRole) {
            $user->update(['role_id' => $superadminRole->id]);
            echo "[UPDATE] Assigned role 'superadmin' to existing user '{$email}'.\n";
        } else {
            echo "[INFO] Administrator '{$email}' already exists with role '{$user->roleSlug()}'.\n";
        }
    }

    // 3. Seed Demo Users for Testing Other Levels
    $demoUsers = [
        [
            'name' => 'Manager Staff',
            'email' => 'manager@syntaxcore.com',
            'password' => 'manager123',
            'role_slug' => 'admin',
        ],
        [
            'name' => 'Regular User',
            'email' => 'user@syntaxcore.com',
            'password' => 'user123',
            'role_slug' => 'user',
        ],
    ];

    foreach ($demoUsers as $demo) {
        $existing = User::findByEmail($demo['email']);
        $role = $rolesMap[$demo['role_slug']] ?? null;
        if (!$existing && $role) {
            $u = new User([
                'name' => $demo['name'],
                'email' => $demo['email'],
                'role_id' => $role->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $u->setPassword($demo['password']);
            $u->save();
            echo "[SUCCESS] Demo user seeded: {$demo['email']} ({$demo['role_slug']})\n";
        }
    }

    // 4. Pastikan user lain yang ada di database juga mendapatkan role default jika masih null
    $allUsers = User::all();
    foreach ($allUsers as $u) {
        if (empty($u->role_id)) {
            // Berikan role superadmin untuk admin@gmail.com, atau admin role untuk lainnya
            $assignedRole = ($u->email === 'admin@gmail.com' || str_contains($u->email, 'admin'))
                ? ($rolesMap['superadmin'] ?? $rolesMap['admin'])
                : ($rolesMap['user'] ?? null);

            if ($assignedRole) {
                $u->update(['role_id' => $assignedRole->id]);
                echo "[UPDATE] Assigned role '{$assignedRole->slug}' to user '{$u->email}'.\n";
            }
        }
    }

    // 5. Seed Menus (Root and Nested Submenus) & Hubungkan Hak Akses (role_menu)
    $menuTreeDefs = [
        [
            'title' => 'Dashboard Overview',
            'icon' => 'fa-solid fa-gauge-high',
            'action' => 'open_dashboard',
            'route' => '/admin',
            'badge' => null,
            'sort_order' => 1,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Profil Saya',
            'icon' => 'fa-solid fa-user-gear',
            'action' => 'open_profile',
            'route' => '/admin/profile',
            'badge' => null,
            'sort_order' => 2,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Master Data',
            'icon' => 'fa-solid fa-folder-tree',
            'action' => null,
            'route' => null,
            'badge' => '2',
            'sort_order' => 3,
            'roles' => ['admin', 'superadmin'],
            'children' => [
                [
                    'title' => 'Manajemen Pengguna',
                    'icon' => 'fa-solid fa-users',
                    'action' => 'open_users',
                    'route' => '/admin/users',
                    'badge' => null,
                    'sort_order' => 1,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Data Peran (Roles)',
                    'icon' => 'fa-solid fa-user-shield',
                    'action' => 'open_roles',
                    'route' => '/admin/roles',
                    'badge' => null,
                    'sort_order' => 2,
                    'roles' => ['admin', 'superadmin'],
                ],
            ],
        ],
        [
            'title' => 'Manajemen CMS',
            'icon' => 'fa-solid fa-newspaper',
            'action' => null,
            'route' => null,
            'badge' => 'CMS',
            'sort_order' => 4,
            'roles' => ['admin', 'superadmin'],
            'children' => [
                [
                    'title' => 'Halaman (Pages)',
                    'icon' => 'fa-solid fa-file-lines',
                    'action' => 'open_cms_pages',
                    'route' => '/admin/cms/pages',
                    'badge' => null,
                    'sort_order' => 1,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Berita & Artikel',
                    'icon' => 'fa-solid fa-newspaper',
                    'action' => 'open_cms_news',
                    'route' => '/admin/cms/news',
                    'badge' => null,
                    'sort_order' => 2,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Kategori & Tag',
                    'icon' => 'fa-solid fa-tags',
                    'action' => 'open_cms_taxonomy',
                    'route' => '/admin/cms/taxonomy',
                    'badge' => null,
                    'sort_order' => 3,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Navigasi Menu Publik',
                    'icon' => 'fa-solid fa-bars-staggered',
                    'action' => 'open_cms_menus',
                    'route' => '/admin/cms/menus',
                    'badge' => null,
                    'sort_order' => 4,
                    'roles' => ['admin', 'superadmin'],
                ],
                [
                    'title' => 'Moderasi Komentar',
                    'icon' => 'fa-solid fa-comments',
                    'action' => 'open_cms_comments',
                    'route' => '/admin/cms/comments',
                    'badge' => null,
                    'sort_order' => 5,
                    'roles' => ['admin', 'superadmin'],
                ],
            ],
        ],
        [
            'title' => 'Laporan Aktivitas',
            'icon' => 'fa-solid fa-chart-line',
            'action' => 'open_reports',
            'route' => '/admin/reports',
            'badge' => null,
            'sort_order' => 5,
            'roles' => ['admin', 'superadmin'],
            'children' => [],
        ],
        [
            'title' => 'Sistem & Konfigurasi',
            'icon' => 'fa-solid fa-server',
            'action' => null,
            'route' => null,
            'badge' => '2',
            'sort_order' => 6,
            'roles' => ['superadmin'],
            'children' => [
                [
                    'title' => 'Database Explorer',
                    'icon' => 'fa-solid fa-database',
                    'action' => 'open_database',
                    'route' => '/admin/database',
                    'badge' => null,
                    'sort_order' => 1,
                    'roles' => ['superadmin'],
                ],
                [
                    'title' => 'Pengaturan Sistem',
                    'icon' => 'fa-solid fa-gears',
                    'action' => 'open_settings',
                    'route' => '/admin/settings',
                    'badge' => null,
                    'sort_order' => 2,
                    'roles' => ['superadmin'],
                ],
            ],
        ],
        [
            'title' => 'Dokumentasi Framework',
            'icon' => 'fa-solid fa-book-open',
            'action' => 'new_tab',
            'route' => 'https://github.com',
            'badge' => 'Docs',
            'sort_order' => 7,
            'roles' => ['user', 'admin', 'superadmin'],
            'children' => [],
        ],
    ];

    $pdo = \Core\Database\Connection::get();

    $seedMenuRecursive = function (array $def, ?int $parentId = null) use (&$seedMenuRecursive, $pdo, $rolesMap) {
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE title = ? AND " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " LIMIT 1");
        if ($parentId === null) {
            $stmt->execute([$def['title']]);
        } else {
            $stmt->execute([$def['title'], $parentId]);
        }
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $insert = $pdo->prepare("INSERT INTO menus (parent_id, title, icon, action, route, badge, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $insert->execute([
                $parentId,
                $def['title'],
                $def['icon'],
                $def['action'] ?? null,
                $def['route'] ?? null,
                $def['badge'] ?? null,
                $def['sort_order'],
            ]);
            $menuId = (int) $pdo->lastInsertId();
            echo "[SUCCESS] Menu seeded: " . ($parentId ? "  └─ " : "") . "{$def['title']}\n";
        } else {
            $menuId = (int) $existing['id'];
            $update = $pdo->prepare("UPDATE menus SET icon = ?, action = ?, route = ?, badge = ?, sort_order = ? WHERE id = ?");
            $update->execute([
                $def['icon'],
                $def['action'] ?? null,
                $def['route'] ?? null,
                $def['badge'] ?? null,
                $def['sort_order'],
                $menuId,
            ]);
        }

        // Hubungkan role_menu
        foreach ($def['roles'] ?? [] as $roleSlug) {
            $role = $rolesMap[$roleSlug] ?? null;
            if ($role) {
                $linkStmt = $pdo->prepare("INSERT IGNORE INTO role_menu (role_id, menu_id) VALUES (?, ?)");
                $linkStmt->execute([$role->id, $menuId]);
            }
        }

        // Rekursif ke children jika ada
        if (!empty($def['children'])) {
            foreach ($def['children'] as $childDef) {
                $seedMenuRecursive($childDef, $menuId);
            }
        }
    };

    foreach ($menuTreeDefs as $rootMenuDef) {
        $seedMenuRecursive($rootMenuDef, null);
    }

    echo "[SUCCESS] All menus and role mappings seeded successfully!\n";

    // 6. Seed CMS Categories
    echo "\n--- Seeding CMS Data ---\n";
    $categoriesData = [
        [
            'name' => 'Umum',
            'slug' => 'umum',
            'color' => '#0d6efd',
            'description' => 'Informasi dan wawasan umum seputar organisasi dan perkembangan terkini.',
        ],
        [
            'name' => 'Teknologi',
            'slug' => 'teknologi',
            'color' => '#198754',
            'description' => 'Inovasi teknologi, rekayasa perangkat lunak, dan arsitektur sistem modern.',
        ],
        [
            'name' => 'Pengumuman',
            'slug' => 'pengumuman',
            'color' => '#ffc107',
            'description' => 'Rilis resmi, berita pembaruan fitur, dan agenda penting.',
        ],
    ];

    $categoriesMap = [];
    foreach ($categoriesData as $cat) {
        $category = Category::findBySlug($cat['slug']);
        if (!$category) {
            $category = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'color' => $cat['color'],
                'description' => $cat['description'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "[SUCCESS] CMS Category seeded: {$cat['name']}\n";
        }
        $categoriesMap[$cat['slug']] = $category;
    }

    // 7. Seed CMS Tags
    $tagsData = ['SyntaxCore', 'Update', 'Tutorial', 'OpenSource', 'WebDev'];
    $tagsMap = [];
    foreach ($tagsData as $tagName) {
        $tag = Tag::firstOrCreateByName($tagName);
        $tagsMap[strtolower($tagName)] = $tag;
        echo "[SUCCESS] CMS Tag ensured: {$tag->name}\n";
    }

    // 8. Seed Default Pages
    $pagesData = [
        [
            'title' => 'Beranda Utama',
            'slug' => 'beranda',
            'page_type' => 'standard',
            'content' => '<h2>Selamat Datang di SyntaxCore</h2><p>SyntaxCore adalah platform web modular modern bertenaga PHP MVC murni yang cepat, elegan, dan fleksibel. Platform ini menghadirkan pengalaman Desktop Window Environment untuk area administratif dan CMS bertenaga tinggi untuk website publik.</p><div class="alert alert-info mt-4"><i class="fa-solid fa-info-circle me-2"></i>Halaman ini dikelola langsung dari menu manajemen CMS di dalam Desktop Window Environment.</div>',
            'meta_title' => 'SyntaxCore - Modern Modular Web Application Framework',
            'meta_description' => 'SyntaxCore adalah sistem aplikasi web modular dengan antarmuka desktop window dan CMS publik terintegrasi.',
            'status' => 'published',
            'sort_order' => 1,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Tentang Kami',
            'slug' => 'tentang-kami',
            'page_type' => 'standard',
            'content' => '<h2>Tentang SyntaxCore Framework</h2><p>SyntaxCore dibangun dengan filosofi kesederhanaan, performa tinggi, dan kebebasan arsitektur. Dirancang untuk memberikan kendali penuh kepada pengembang tanpa ketergantungan berlebih (zero unnecessary bloat).</p><h4>Fitur Unggulan</h4><ul><li><strong>Desktop Environment UI:</strong> Pengalaman windowing multitasking di browser.</li><li><strong>Dynamic CMS Engine:</strong> Pengelolaan artikel, kategori, tags, halaman custom, dan sistem komentar fleksibel.</li><li><strong>Role-Based Access Control:</strong> Pengaturan hak akses granular untuk tiap menu dan modul.</li></ul>',
            'meta_title' => 'Tentang Kami - SyntaxCore Framework',
            'meta_description' => 'Mengenal filosofi, arsitektur, dan fitur utama SyntaxCore.',
            'status' => 'published',
            'sort_order' => 2,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Berita & Pengumuman',
            'slug' => 'berita',
            'page_type' => 'news_index',
            'content' => '<p class="lead text-muted">Dapatkan rilis, wawasan teknologi, dan update terbaru langsung dari tim pengembang SyntaxCore.</p>',
            'meta_title' => 'Warta & Berita Terkini - SyntaxCore',
            'meta_description' => 'Arsip berita, pengumuman, dan artikel teknologi terkini SyntaxCore.',
            'status' => 'published',
            'sort_order' => 3,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Baca Berita',
            'slug' => 'baca-berita',
            'page_type' => 'news_single',
            'content' => null,
            'meta_title' => 'Detail Berita - SyntaxCore',
            'meta_description' => 'Halaman pembaca detail berita dengan interaksi komentar terintegrasi.',
            'status' => 'published',
            'sort_order' => 4,
            'comment_settings' => json_encode([
                'enabled' => true,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 15,
            ]),
        ],
    ];

    foreach ($pagesData as $pData) {
        $existingPage = Page::findBySlug($pData['slug']);
        if (!$existingPage) {
            Page::create([
                'title' => $pData['title'],
                'slug' => $pData['slug'],
                'page_type' => $pData['page_type'],
                'content' => $pData['content'],
                'meta_title' => $pData['meta_title'],
                'meta_description' => $pData['meta_description'],
                'status' => $pData['status'],
                'sort_order' => $pData['sort_order'],
                'comment_settings' => $pData['comment_settings'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "[SUCCESS] CMS Page seeded: {$pData['title']} ({$pData['slug']})\n";
        }
    }

    // 9. Seed Initial News
    $firstUser = User::findByEmail('admin@syntaxcore.com') ?? User::findByEmail('admin@gmail.com');
    $authorId = $firstUser ? $firstUser->id : 1;

    $sampleNews = [
        [
            'title' => 'SyntaxCore Merilis Public CMS Engine Terpadu',
            'slug' => 'syntaxcore-merilis-public-cms-engine-terpadu',
            'category_id' => $categoriesMap['pengumuman']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'SyntaxCore kini melengkapi ekosistemnya dengan Public CMS Engine yang memungkinkan pembuatan custom pages, artikel, taksonomi dinamis, dan kustomisasi komentar.',
            'content' => '<p>Kami sangat antusias mengumumkan peluncuran <strong>Public CMS Engine SyntaxCore</strong>. Modul ini melengkapi pengalaman Desktop Environment administratif yang telah hadir sebelumnya dengan portal publik yang elegan, cepat, dan sepenuhnya modular.</p><p>Fitur utama yang disertakan dalam rilis ini:</p><ul><li><strong>Custom Pages:</strong> Buat halaman statis, agregator berita, atau pembaca berita dengan template mandiri.</li><li><strong>Taksonomi Kategori & Tag:</strong> Organisasi konten multi-dimensi dengan pelabelan warna dan metadata SEO.</li><li><strong>Sistem Komentar Dinamis:</strong> Pengunjung dan member dapat berdiskusi pada berita, dengan opsi gaya tata letak komentar (Cards, Threaded, Minimal) yang dapat dikustomisasi per halaman pembaca.</li><li><strong>Navigasi Menu Bertingkat:</strong> Pengaturan menu publik fleksibel langsung dari backend admin.</li></ul><p>Silakan bereksplorasi dan berikan tanggapan Anda melalui kolom komentar di bawah!</p>',
            'featured_image' => null,
            'allow_comments' => 1,
            'views_count' => 15,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'tags' => ['syntaxcore', 'update', 'opensource'],
        ],
        [
            'title' => 'Panduan Desain Halaman Publik & Kustomisasi Komentar',
            'slug' => 'panduan-desain-halaman-publik-kustomisasi-komentar',
            'category_id' => $categoriesMap['teknologi']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'Pelajari cara mengkonfigurasi tampilan komentar dan membuat halaman berita kustom di SyntaxCore tanpa menyentuh kode backend.',
            'content' => '<p>Salah satu kekuatan arsitektur CMS SyntaxCore adalah pemisahan antara entitas berita (News) dan halaman penampil (Pages). Komentar secara data melekat pada berita, namun gaya penampilannya (apakah bergaya Cards yang luas, Threaded yang mendukung balasan bertingkat, atau Minimal yang ramping) dikontrol sepenuhnya oleh halaman pembaca yang menampilkannya.</p><p>Pengembang dapat dengan mudah membuat halaman berita baru dengan tipe <code>news_index</code> atau <code>news_single</code> dan mengatur parameter JSON <code>comment_settings</code> sesuai kebutuhan proyek.</p>',
            'featured_image' => null,
            'allow_comments' => 1,
            'views_count' => 8,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'tags' => ['tutorial', 'syntaxcore', 'webdev'],
        ],
    ];

    $createdNews = [];
    foreach ($sampleNews as $nData) {
        $existingNews = News::findBySlug($nData['slug']);
        if (!$existingNews) {
            $newsModel = News::create([
                'title' => $nData['title'],
                'slug' => $nData['slug'],
                'category_id' => $nData['category_id'],
                'user_id' => $nData['user_id'],
                'summary' => $nData['summary'],
                'content' => $nData['content'],
                'featured_image' => $nData['featured_image'],
                'allow_comments' => $nData['allow_comments'],
                'views_count' => $nData['views_count'],
                'status' => $nData['status'],
                'published_at' => $nData['published_at'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Hubungkan tags
            $tagIds = [];
            foreach ($nData['tags'] as $tSlug) {
                if (isset($tagsMap[$tSlug])) {
                    $tagIds[] = $tagsMap[$tSlug]->id;
                }
            }
            $newsModel->syncTags($tagIds);
            echo "[SUCCESS] CMS News seeded: {$nData['title']}\n";
            $createdNews[] = $newsModel;
        } else {
            $createdNews[] = $existingNews;
        }
    }

    // 10. Seed Comments
    if (!empty($createdNews[0])) {
        $firstNews = $createdNews[0];
        $existingComments = Comment::where('news_id', '=', $firstNews->id);
        if (empty($existingComments)) {
            $c1 = Comment::create([
                'news_id' => $firstNews->id,
                'parent_id' => null,
                'user_id' => $authorId,
                'author_name' => 'Administrator',
                'author_email' => 'admin@syntaxcore.com',
                'content' => 'Selamat datang semuanya! Modul CMS publik telah aktif dan siap digunakan. Silakan uji coba fitur komentar ini.',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (SyntaxCore Seeder)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ]);

            Comment::create([
                'news_id' => $firstNews->id,
                'parent_id' => $c1->id,
                'user_id' => null,
                'author_name' => 'Pengunjung Tamu',
                'author_email' => 'guest@example.com',
                'content' => 'Keren sekali! Tampilan bersih dan sangat responsif.',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (SyntaxCore Seeder)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ]);
            echo "[SUCCESS] Sample comments seeded for news '{$firstNews->title}'\n";
        }
    }

    // 11. Seed Public Menus
    $publicMenusData = [
        [
            'title' => 'Beranda',
            'link_type' => 'page',
            'link_target' => 'beranda',
            'url' => '/',
            'sort_order' => 1,
            'children' => [],
        ],
        [
            'title' => 'Berita & Artikel',
            'link_type' => 'page',
            'link_target' => 'berita',
            'url' => '/berita',
            'sort_order' => 2,
            'children' => [
                [
                    'title' => 'Teknologi',
                    'link_type' => 'news_category',
                    'link_target' => 'teknologi',
                    'url' => null,
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Pengumuman',
                    'link_type' => 'news_category',
                    'link_target' => 'pengumuman',
                    'url' => null,
                    'sort_order' => 2,
                ],
            ],
        ],
        [
            'title' => 'Tentang Kami',
            'link_type' => 'page',
            'link_target' => 'tentang-kami',
            'url' => '/tentang-kami',
            'sort_order' => 3,
            'children' => [],
        ],
    ];

    $seedPublicMenuRecursive = function (array $def, ?int $parentId = null) use (&$seedPublicMenuRecursive, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM public_menus WHERE title = ? AND " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " LIMIT 1");
        if ($parentId === null) {
            $stmt->execute([$def['title']]);
        } else {
            $stmt->execute([$def['title'], $parentId]);
        }
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $insert = $pdo->prepare("INSERT INTO public_menus (parent_id, title, link_type, link_target, url, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $insert->execute([
                $parentId,
                $def['title'],
                $def['link_type'],
                $def['link_target'] ?? null,
                $def['url'] ?? null,
                $def['sort_order'],
            ]);
            $menuId = (int) $pdo->lastInsertId();
            echo "[SUCCESS] Public Menu seeded: " . ($parentId ? "  └─ " : "") . "{$def['title']}\n";
        } else {
            $menuId = (int) $existing['id'];
        }

        if (!empty($def['children'])) {
            foreach ($def['children'] as $childDef) {
                $seedPublicMenuRecursive($childDef, $menuId);
            }
        }
    };

    foreach ($publicMenusData as $pMenu) {
        $seedPublicMenuRecursive($pMenu, null);
    }
    echo "[SUCCESS] Public menus seeded successfully!\n";
} catch (\Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
