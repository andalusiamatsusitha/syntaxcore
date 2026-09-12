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
    echo "\n--- Seeding CMS Data for PKBM S. Supriadi ---\n";
    $categoriesData = [
        [
            'name' => 'Artikel',
            'slug' => 'artikel',
            'color' => '#2db700',
            'description' => 'Artikel edukatif dan liputan kegiatan warga belajar PKBM S. Supriadi.',
        ],
        [
            'name' => 'Berita Pendidikan',
            'slug' => 'berita-pendidikan',
            'color' => '#0d6efd',
            'description' => 'Informasi dan warta seputar pendidikan kesetaraan dan kurikulum merdeka.',
        ],
        [
            'name' => 'Ujian',
            'slug' => 'ujian',
            'color' => '#dc3545',
            'description' => 'Jadwal dan pelaksanaan asesmen, TKA, dan UPK kesetaraan Paket A, B, dan C.',
        ],
        [
            'name' => 'Ramadhan',
            'slug' => 'ramadhan',
            'color' => '#198754',
            'description' => 'Agenda keagamaan dan kegiatan sosial warga belajar selama bulan Ramadhan.',
        ],
        [
            'name' => 'Teknologi',
            'slug' => 'teknologi',
            'color' => '#6f42c1',
            'description' => 'Pemanfaatan TIK, kelas komputer, dan sistem pembelajaran digital mandiri.',
        ],
        [
            'name' => 'Pengumuman',
            'slug' => 'pengumuman',
            'color' => '#ffc107',
            'description' => 'Pengumuman resmi, agenda akademik, dan informasi PPDB sekolah.',
        ],
        [
            'name' => 'Umum',
            'slug' => 'umum',
            'color' => '#0dcaf0',
            'description' => 'Informasi umum dan kegiatan pemberdayaan masyarakat di lingkungan sekolah.',
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
        } else {
            $category->update([
                'name' => $cat['name'],
                'color' => $cat['color'],
                'description' => $cat['description'],
            ]);
        }
        $categoriesMap[$cat['slug']] = $category;
    }

    // 7. Seed CMS Tags
    $tagsData = ['PKBM', 'Paket A', 'Paket B', 'Paket C', 'TKA', 'SuperCamp', 'Pendidikan', 'Malang', 'SyntaxCore', 'Tutorial', 'WebDev'];
    $tagsMap = [];
    foreach ($tagsData as $tagName) {
        $tag = Tag::firstOrCreateByName($tagName);
        $tagsMap[strtolower(str_replace(' ', '-', $tagName))] = $tag;
        echo "[SUCCESS] CMS Tag ensured: {$tag->name}\n";
    }

    // 8. Seed Default Pages for PKBM S. Supriadi
    $pagesData = [
        [
            'title' => 'Beranda Utama',
            'slug' => 'beranda',
            'page_type' => 'standard',
            'content' => '<h2>Selamat Datang di PKBM S. Supriadi</h2><p>PKBM S. Supriadi merupakan lembaga pendidikan nonformal resmi di bawah naungan Dinas Pendidikan Kota Malang dengan NPSN P9962749 dan telah terakreditasi. Kami hadir memberikan kesempatan belajar yang fleksibel dan berkualitas bagi siapa saja yang ingin melanjutkan pendidikan kesetaraan Paket A (setara SD), Paket B (setara SMP), dan Paket C (setara SMA).</p>',
            'meta_title' => 'Website Resmi PKBM S.Supriadi - Sekolah Kesetaraan Kota Malang',
            'meta_description' => 'Website Resmi PKBM S.Supriadi Kota Malang menyelenggarakan pendidikan kesetaraan Paket A, B, C dengan sistem belajar fleksibel dan keterampilan vokasi.',
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
            'title' => 'Profil Sekolah',
            'slug' => 'profil-sekolah',
            'page_type' => 'standard',
            'content' => '<div class="mb-4 text-center"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_9-1100x600.jpg" alt="Profil PKBM S.Supriadi" class="img-fluid rounded-3 shadow-sm mb-3"></div><h2>Profil PKBM S.Supriadi</h2><p><strong>PKBM S.Supriadi</strong> merupakan lembaga pendidikan nonformal yang berkomitmen memberikan kesempatan belajar bagi masyarakat luas. PKBM ini hadir sebagai solusi pendidikan bagi siapa saja yang ingin melanjutkan pendidikan namun memiliki keterbatasan waktu, usia, maupun kesempatan untuk mengikuti pendidikan formal.</p><p>PKBM S.Supriadi menyelenggarakan program pendidikan kesetaraan yang meliputi <strong>Paket A (setara SD), Paket B (setara SMP), dan Paket C (setara SMA)</strong>. Dengan sistem pembelajaran yang fleksibel dan menyesuaikan kebutuhan peserta didik, proses belajar dapat dilakukan secara efektif sehingga memudahkan masyarakat untuk tetap melanjutkan pendidikan.</p><p>Selain pembelajaran akademik, PKBM S.Supriadi juga memberikan berbagai kegiatan pengembangan keterampilan seperti <strong>kelas komputer, cooking class, serta kegiatan pembelajaran lainnya</strong> yang bertujuan untuk meningkatkan kemampuan dan kemandirian peserta didik. Hal ini diharapkan dapat menjadi bekal yang bermanfaat bagi kehidupan serta masa depan mereka.</p><p>Dengan dukungan tenaga pendidik yang berkompeten serta fasilitas pembelajaran yang memadai, PKBM S.Supriadi terus berupaya menjadi lembaga pendidikan yang <strong>bermanfaat bagi masyarakat, membentuk karakter yang baik, serta membantu peserta didik meraih masa depan yang lebih baik</strong>.</p>',
            'meta_title' => 'Profil Sekolah - PKBM S.Supriadi',
            'meta_description' => 'Mengenal profil PKBM S.Supriadi, lembaga pendidikan nonformal di Kota Malang.',
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
            'title' => 'Identitas Sekolah',
            'slug' => 'identitas-sekolah',
            'page_type' => 'standard',
            'content' => '<div class="mb-4 text-center"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_8-e1772973100488-1100x600.jpg" alt="Identitas PKBM S.Supriadi" class="img-fluid rounded-3 shadow-sm mb-3"></div><h2>Identitas Resmi Sekolah</h2><table class="table table-bordered table-striped mt-3"><tbody><tr><th width="35%">Nama Sekolah</th><td><strong>PKBM S.SUPRIADI</strong></td></tr><tr><th>NPSN</th><td><span class="badge bg-success">P9962749</span></td></tr><tr><th>Status Lembaga</th><td>Swasta</td></tr><tr><th>Bentuk Pendidikan</th><td>Pendidikan Nonformal (PKBM)</td></tr><tr><th>Status Kepemilikan</th><td>Yayasan</td></tr><tr><th>SK Pendirian Sekolah</th><td>AHU-0006544.AH.01.04.Tahun 2016</td></tr><tr><th>Tanggal SK Pendirian</th><td>2016-02-28</td></tr><tr><th>SK Izin Operasional</th><td>420.3/0019/35.73.406/2022</td></tr><tr><th>Tanggal SK Operasional</th><td>2022-03-31</td></tr></tbody></table><h4 class="mt-4 mb-2">Alamat & Lokasi Sekolah</h4><table class="table table-bordered table-striped"><tbody><tr><th width="35%">Alamat</th><td>Jl. S.Supriadi IX No.42 RT.13 RW.04</td></tr><tr><th>Desa / Kelurahan</th><td>Sukun</td></tr><tr><th>Kecamatan</th><td>Sukun</td></tr><tr><th>Kabupaten / Kota</th><td>Kota Malang</td></tr><tr><th>Provinsi</th><td>Jawa Timur</td></tr><tr><th>Kode Pos</th><td>65147</td></tr><tr><th>Telepon / WhatsApp</th><td>085954447600</td></tr><tr><th>Email Resmi</th><td>pkbmsupriadi1@gmail.com</td></tr></tbody></table>',
            'meta_title' => 'Identitas Sekolah - PKBM S.Supriadi',
            'meta_description' => 'Data legalitas resmi, NPSN P9962749, SK Pendirian, dan alamat PKBM S.Supriadi Malang.',
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
            'title' => 'Visi & Misi',
            'slug' => 'visi-misi',
            'page_type' => 'standard',
            'content' => '<div class="mb-4 text-center"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_7-1100x600.jpg" alt="Visi dan Misi PKBM S.Supriadi" class="img-fluid rounded-3 shadow-sm mb-3"></div><div class="p-4 bg-light rounded-3 border mb-4"><h3 class="text-success fw-bold mb-2"><i class="fa-solid fa-bullseye me-2"></i>Visi Sekolah</h3><p class="lead mb-0" style="font-size: 17px; line-height: 1.7;">"Menjadi lembaga pendidikan masyarakat yang berkualitas, bermanfaat bagi lingkungan sekitar, serta mampu membentuk peserta didik yang berakhlak baik, berpengetahuan, dan memiliki keterampilan yang berguna untuk kehidupan serta masa depan yang lebih baik."</p></div><h3 class="text-success fw-bold mb-3"><i class="fa-solid fa-list-check me-2"></i>Misi Sekolah</h3><ol class="list-group list-group-numbered shadow-sm mb-4"><li class="list-group-item p-3">Menyelenggarakan pendidikan yang terbuka dan bermanfaat bagi seluruh lapisan masyarakat sebagai sarana meningkatkan kualitas sumber daya manusia.</li><li class="list-group-item p-3">Menanamkan nilai-nilai keagamaan, akhlak mulia, dan sikap positif dalam setiap proses pembelajaran sehingga peserta didik memiliki karakter yang baik.</li><li class="list-group-item p-3">Mengembangkan pengetahuan, keterampilan, serta kemampuan peserta didik agar mampu mandiri, produktif, dan siap menghadapi tantangan di masa depan.</li><li class="list-group-item p-3">Menciptakan lingkungan belajar yang nyaman, inklusif, dan mendukung perkembangan potensi setiap peserta didik.</li><li class="list-group-item p-3">Mendorong peserta didik untuk memiliki semangat belajar sepanjang hayat demi meraih masa depan yang lebih baik.</li></ol>',
            'meta_title' => 'Visi & Misi - PKBM S.Supriadi',
            'meta_description' => 'Visi dan 5 Misi utama PKBM S.Supriadi dalam mewujudkan pendidikan kesetaraan berkualitas di Kota Malang.',
            'status' => 'published',
            'sort_order' => 4,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Sambutan Kepala Sekolah',
            'slug' => 'sambutan-kepala-sekolah',
            'page_type' => 'standard',
            'content' => '<div class="row align-items-center g-4 mb-4"><div class="col-md-4 text-center"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_batik_700kb-e1772858295746.jpg" alt="Muh Fajar Mujahid, S.Pd" class="img-fluid rounded-3 shadow-sm border" style="max-height: 280px; object-fit: cover;"></div><div class="col-md-8"><span class="badge bg-success mb-2">Kepala PKBM S. Supriadi</span><h3 class="fw-bold mb-1">Muh Fajar Mujahid, S.Pd</h3><p class="text-muted small">Pusat Kegiatan Belajar Masyarakat (PKBM) S. Supriadi Kota Malang</p></div></div><p><em>Assalamu’alaikum warahmatullahi wabarakatuh,</em></p><p>Puji syukur kita panjatkan ke hadirat Allah SWT atas segala rahmat dan karunia-Nya sehingga website PKBM ini dapat hadir sebagai sarana informasi dan komunikasi bagi masyarakat. Melalui website ini kami berharap masyarakat dapat lebih mengenal berbagai program, kegiatan, serta layanan pendidikan yang diselenggarakan di PKBM. Kehadiran website ini juga menjadi bentuk komitmen kami dalam memberikan informasi yang terbuka, transparan, dan mudah diakses oleh seluruh masyarakat.</p><p>PKBM merupakan lembaga pendidikan nonformal yang memberikan kesempatan belajar bagi masyarakat yang ingin melanjutkan pendidikan. Program pendidikan yang kami selenggarakan meliputi Paket A, Paket B, dan Paket C, yang dirancang untuk membantu masyarakat memperoleh pendidikan yang setara dengan pendidikan formal. PKBM juga <strong>terbuka bagi siapa pun dan dari berbagai kalangan tanpa batasan usia</strong>, sehingga setiap orang memiliki kesempatan yang sama untuk belajar, meningkatkan pengetahuan, serta mengembangkan potensi diri.</p><p>Akhir kata, kami mengucapkan terima kasih kepada semua pihak yang telah mendukung keberadaan dan perkembangan PKBM. Kami berharap melalui website ini dapat terjalin komunikasi yang baik antara lembaga, peserta didik, orang tua, serta masyarakat luas. Semoga PKBM dapat terus menjadi tempat belajar yang bermanfaat, menciptakan generasi yang berilmu, mandiri, dan siap menghadapi tantangan masa depan.</p><p><em>Wassalamu’alaikum warahmatullahi wabarakatuh.</em></p>',
            'meta_title' => 'Sambutan Kepala Sekolah - PKBM S.Supriadi',
            'meta_description' => 'Sambutan resmi Kepala Sekolah PKBM S.Supriadi, Bapak Muh Fajar Mujahid, S.Pd.',
            'status' => 'published',
            'sort_order' => 5,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Sejarah Singkat',
            'slug' => 'sejarah-singkat',
            'page_type' => 'standard',
            'content' => '<h2>Sejarah Singkat PKBM S.Supriadi</h2><p>PKBM S.Supriadi berawal dari kepedulian para pendidik dan tokoh masyarakat di kawasan Sukun Kota Malang terhadap warga yang mengalami putus sekolah atau terkendala mengikuti jalur pendidikan formal karena faktor ekonomi, usia, dan jam kerja.</p><p>Berdiri secara resmi sejak tahun 2016 melalui SK AHU-0006544.AH.01.04.Tahun 2016 dan diperkuat dengan Izin Operasional Dinas Pendidikan Kota Malang No. 420.3/0019/35.73.406/2022, lembaga ini terus berinovasi memadukan kurikulum pendidikan kesetaraan nasional dengan pelatihan kecakapan hidup (life skills), teknologi informasi, dan kewirausahaan.</p>',
            'meta_title' => 'Sejarah Singkat - PKBM S.Supriadi',
            'meta_description' => 'Sejarah berdirinya PKBM S.Supriadi dalam memajukan pendidikan kesetaraan di Malang.',
            'status' => 'published',
            'sort_order' => 6,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Struktur Organisasi',
            'slug' => 'struktur-organisasi',
            'page_type' => 'standard',
            'content' => '<h2>Struktur Organisasi PKBM S.Supriadi</h2><p class="text-muted">Susunan kepengurusan dan manajemen operasional lembaga:</p><div class="row g-3 my-3"><div class="col-md-6"><div class="card p-3 border shadow-sm"><h5>Ketua Yayasan / Penyelenggara</h5><p class="text-muted small mb-0">Dewan Pembina Yayasan PKBM S. Supriadi</p></div></div><div class="col-md-6"><div class="card p-3 border shadow-sm border-success"><h5>Kepala PKBM</h5><p class="text-success fw-semibold small mb-0">Muh Fajar Mujahid, S.Pd</p></div></div><div class="col-md-6"><div class="card p-3 border shadow-sm"><h5>Operator Sekolah & IT Support</h5><p class="text-muted small mb-0">Dimas Arya Agung Nugraha, S.Pd</p></div></div><div class="col-md-6"><div class="card p-3 border shadow-sm"><h5>Koordinator Tutor & Kurikulum</h5><p class="text-muted small mb-0">Retno Wahyu Kusuma, S.Pd</p></div></div></div>',
            'meta_title' => 'Struktur Organisasi - PKBM S.Supriadi',
            'meta_description' => 'Struktur organisasi dan jajaran pengurus PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 7,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Staf Pengajar',
            'slug' => 'staf-pengajar',
            'page_type' => 'standard',
            'content' => '<h2>Daftar Staf Pengajar (Guru / Tutor)</h2><p class="text-muted mb-4">Tenaga pendidik berkompeten dan berdedikasi tinggi di PKBM S.Supriadi:</p><div class="row g-4"><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_formal_hijab_700kb-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Adinda Sukma Nur Chaya, S.Pd</h5><p class="text-muted small mb-0">Bahasa Jawa, Pendidikan Kewarganegaraan</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/08/WhatsApp-Image-2026-08-26-at-12.46.21-386x500.jpeg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">ALFINDHA ROSA SANITA, S.Pd</h5><p class="text-muted small mb-0">Ilmu Pengetahuan Alam, Matematika</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_flanel_700kb-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Dimas Arya Agung Nugraha, S.Pd</h5><p class="text-muted small mb-0">Teknologi Informasi & Komunikasi (TIK)</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_batik_1200x245-1-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Ilham Gus Adhiim, M.Pd</h5><p class="text-muted small mb-0">Bahasa Inggris</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/pasfoto_700kb-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Retno Wahyu Kusuma, S.Pd</h5><p class="text-muted small mb-0">Bahasa Indonesia, Cooking Class, IPS</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_work_jacket_700kb-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Zulius Nilam Cahya, S.Pd</h5><p class="text-muted small mb-0">Bahasa Indonesia</p></div></div></div>',
            'meta_title' => 'Staf Pengajar - PKBM S.Supriadi',
            'meta_description' => 'Daftar guru pengajar dan tutor berpengalaman di PKBM S.Supriadi Malang.',
            'status' => 'published',
            'sort_order' => 8,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Staf Tenaga Kependidikan',
            'slug' => 'staf-tenaga-kependidikan',
            'page_type' => 'standard',
            'content' => '<h2>Staf Tenaga Kependidikan</h2><p class="text-muted mb-4">Mendukung kelancaran layanan administrasi, pendataan Dapodik, dan operasional warga belajar:</p><div class="row g-4"><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_flanel_700kb-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Dimas Arya Agung Nugraha, S.Pd</h5><p class="text-muted small mb-0">Operator Sekolah & Dapodik</p></div></div><div class="col-md-4 col-sm-6"><div class="card h-100 border text-center p-3 shadow-sm"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_batik_700kb-e1772858295746-386x500.jpg" class="rounded-3 img-fluid mb-3 mx-auto" style="height: 180px; object-fit: cover;"><h5 class="fw-bold mb-1">Muh Fajar Mujahid, S.Pd</h5><p class="text-muted small mb-0">Kepala Sekolah</p></div></div></div>',
            'meta_title' => 'Staf Tenaga Kependidikan - PKBM S.Supriadi',
            'meta_description' => 'Profil staf administrasi dan operator sekolah PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 9,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Fasilitas Sekolah',
            'slug' => 'fasilitas-sekolah',
            'page_type' => 'standard',
            'content' => '<h2>Fasilitas Pendukung Pembelajaran</h2><p>Guna menunjang efektivitas kegiatan belajar mengajar, PKBM S.Supriadi menyediakan berbagai sarana dan prasarana memadai:</p><div class="row g-4 mt-2"><div class="col-md-6"><div class="card border-0 shadow-sm overflow-hidden"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_6-1070x900.jpg" class="card-img-top" style="height: 220px; object-fit: cover;"><div class="card-body"><h5>Laboratorium Komputer</h5><p class="text-muted small mb-0">Dilengkapi dengan perangkat komputer modern dan koneksi internet stabil untuk pembelajaran TIK, ujian berbasis daring, dan pelatihan digital marketing.</p></div></div></div><div class="col-md-6"><div class="card border-0 shadow-sm overflow-hidden"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_16-1200x845.jpg" class="card-img-top" style="height: 220px; object-fit: cover;"><div class="card-body"><h5>Ruang Kelas Nyaman & Fleksibel</h5><p class="text-muted small mb-0">Ruang belajar yang bersih, kondusif, dan mendukung pembelajaran interaktif tatap muka maupun diskusi kelompok.</p></div></div></div></div>',
            'meta_title' => 'Fasilitas Sekolah - PKBM S.Supriadi',
            'meta_description' => 'Fasilitas laboratorium komputer dan sarana belajar PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 10,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Galeri Sekolah',
            'slug' => 'galeri-sekolah',
            'page_type' => 'standard',
            'content' => '<h2>Galeri Dokumentasi Kegiatan</h2><p class="text-muted mb-4">Kilas balik aktivitas, suasana belajar di alam terbuka, dan momen kebersamaan warga belajar PKBM S.Supriadi:</p><div class="row g-3"><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/05/WhatsApp-Image-2026-05-06-at-20.55.03-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/05/WhatsApp-Image-2026-05-05-at-18.22.12-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-14-at-22.04.02-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-13-at-15.25.31-1-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-05-at-13.52.13-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div><div class="col-md-4"><img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-11-at-09.27.27-2-680x554.jpeg" class="img-fluid rounded-3 shadow-sm w-100" style="height: 200px; object-fit: cover;"></div></div>',
            'meta_title' => 'Galeri Kegiatan - PKBM S.Supriadi',
            'meta_description' => 'Foto dan dokumentasi kegiatan belajar warga PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 11,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Intrakurikuler',
            'slug' => 'ekstrakurikuler',
            'page_type' => 'standard',
            'content' => '<h2>Program Intrakurikuler & Keterampilan Vokasi</h2><p>Selain kurikulum akademik kesetaraan, PKBM S.Supriadi membekali warga belajar dengan berbagai program keterampilan aplikatif:</p><div class="row g-4 mt-2"><div class="col-md-6"><div class="card border shadow-sm p-3 h-100"><div class="d-flex align-items-center gap-3 mb-2"><i class="fa-solid fa-laptop-code text-success fs-3"></i><h5 class="mb-0">Computer Class</h5></div><p class="text-muted small mb-0">Pembelajaran keterampilan komputer praktis meliputi administrasi perkantoran, desain grafis, dan pemanfaatan internet untuk produktivitas kerja.</p></div></div><div class="col-md-6"><div class="card border shadow-sm p-3 h-100"><div class="d-flex align-items-center gap-3 mb-2"><i class="fa-solid fa-utensils text-success fs-3"></i><h5 class="mb-0">Cooking Class</h5></div><p class="text-muted small mb-0">Pelatihan tata boga dan kreasi kuliner untuk menumbuhkan minat dan keterampilan kewirausahaan di bidang kuliner.</p></div></div><div class="col-md-6"><div class="card border shadow-sm p-3 h-100"><div class="d-flex align-items-center gap-3 mb-2"><i class="fa-solid fa-language text-success fs-3"></i><h5 class="mb-0">English Club</h5></div><p class="text-muted small mb-0">Wadah latihan percakapan bahasa Inggris santai dan aplikatif untuk meningkatkan rasa percaya diri dan daya saing global.</p></div></div><div class="col-md-6"><div class="card border shadow-sm p-3 h-100"><div class="d-flex align-items-center gap-3 mb-2"><i class="fa-solid fa-campground text-success fs-3"></i><h5 class="mb-0">SuperCamp & Outing Class</h5></div><p class="text-muted small mb-0">Kegiatan edukasi di luar kelas dan perkemahan di alam terbuka untuk melatih kepemimpinan, kemandirian, dan kerja sama tim.</p></div></div></div>',
            'meta_title' => 'Intrakurikuler - PKBM S.Supriadi',
            'meta_description' => 'Program intrakurikuler dan kelas vokasi keterampilan di PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 12,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Kontak',
            'slug' => 'kontak',
            'page_type' => 'standard',
            'content' => '<h2>Hubungi Kami</h2><p class="text-muted">Untuk informasi pendaftaran, konsultasi program kesetaraan, atau kemitraan, silakan hubungi kontak resmi kami di bawah ini:</p><div class="row g-4 my-3"><div class="col-md-6"><div class="p-4 bg-light rounded-3 border h-100"><h5 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-success me-2"></i>Alamat Sekolah</h5><p class="text-muted mb-2">Jl. S.Supriadi IX No.42 RT.13 RW.04, Kel. Sukun, Kec. Sukun, Kota Malang, Jawa Timur 65147</p><hr><h5 class="fw-bold mb-3"><i class="fa-solid fa-phone text-success me-2"></i>Telepon & WhatsApp</h5><p class="text-muted mb-2"><a href="https://wa.me/6285954447600" target="_blank" class="text-decoration-none fw-semibold text-dark">085954447600 (Chat WA)</a></p><hr><h5 class="fw-bold mb-3"><i class="fa-solid fa-envelope text-success me-2"></i>Email Resmi</h5><p class="text-muted mb-0"><a href="mailto:pkbmsupriadi1@gmail.com" class="text-decoration-none text-dark">pkbmsupriadi1@gmail.com</a></p></div></div><div class="col-md-6"><div class="p-4 bg-light rounded-3 border h-100"><h5 class="fw-bold mb-3"><i class="fa-solid fa-share-nodes text-success me-2"></i>Kanal Media Sosial</h5><ul class="list-unstyled d-flex flex-column gap-2 mb-0"><li><a href="https://www.instagram.com/pkbmssupriadi/" target="_blank" class="text-decoration-none text-dark"><i class="fa-brands fa-instagram text-danger me-2"></i>Instagram: @pkbmssupriadi</a></li><li><a href="https://www.youtube.com/@PKBMSUPRIADI" target="_blank" class="text-decoration-none text-dark"><i class="fa-brands fa-youtube text-danger me-2"></i>YouTube: @PKBMSUPRIADI</a></li><li><a href="https://www.tiktok.com/@pkbmssupriadi" target="_blank" class="text-decoration-none text-dark"><i class="fa-brands fa-tiktok me-2"></i>TikTok: @pkbmssupriadi</a></li></ul></div></div></div>',
            'meta_title' => 'Kontak Kami - PKBM S.Supriadi',
            'meta_description' => 'Kontak resmi, telepon WA 085954447600, email, dan alamat PKBM S.Supriadi Malang.',
            'status' => 'published',
            'sort_order' => 13,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Kelulusan',
            'slug' => 'kelulusan',
            'page_type' => 'standard',
            'content' => '<h2>Informasi Kelulusan Ujian Kesetaraan</h2><p>Pengumuman kelulusan resmi bagi peserta didik Paket A, Paket B, dan Paket C PKBM S.Supriadi Kota Malang diumumkan secara daring melalui portal ini atau dapat diambil langsung di kantor operasional sekolah.</p><div class="alert alert-success mt-4"><i class="fa-solid fa-circle-check me-2"></i>Selamat kepada seluruh alumni yang telah menyelesaikan program pendidikan kesetaraan dengan sukses. Ijazah resmi setara formal diterbitkan oleh Kementerian Pendidikan dasar dan Menengah.</div>',
            'meta_title' => 'Informasi Kelulusan - PKBM S.Supriadi',
            'meta_description' => 'Pengumuman kelulusan Paket A, Paket B, dan Paket C PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 14,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'PPDB 2026',
            'slug' => 'ppdb',
            'page_type' => 'standard',
            'content' => '<h2>Penerimaan Peserta Didik Baru (PPDB 2026)</h2><p class="lead">Pendaftaran Warga Belajar Baru PKBM S.Supriadi Tahun Ajaran 2026/2027 telah dibuka!</p><div class="row g-4 my-3"><div class="col-md-4"><div class="card h-100 border p-3 shadow-sm border-success"><h4>Paket A (Setara SD)</h4><p class="text-muted small">Terbuka untuk usia sekolah dasar yang terkendala formal atau masyarakat dewasa yang belum memiliki ijazah SD.</p></div></div><div class="col-md-4"><div class="card h-100 border p-3 shadow-sm border-primary"><h4>Paket B (Setara SMP)</h4><p class="text-muted small">Program kesetaraan jenjang menengah pertama dengan waktu belajar yang dapat disesuaikan.</p></div></div><div class="col-md-4"><div class="card h-100 border p-3 shadow-sm border-info"><h4>Paket C (Setara SMA)</h4><p class="text-muted small">Pilihan tepat bagi pekerja yang ingin memiliki ijazah SMA untuk kenaikan karir atau lanjut kuliah.</p></div></div></div><div class="p-4 bg-light rounded border text-center"><h5 class="fw-bold mb-2">Ingin Mendaftar atau Berkonsultasi?</h5><p class="text-muted small mb-3">Hubungi staf administrasi kami via WhatsApp langsung untuk panduan formulir dan syarat berkas.</p><a href="https://wa.me/6285954447600?text=Halo%20Admin%20PKBM%20S.Supriadi,%20saya%20ingin%20mendaftar%20PPDB%202026" target="_blank" class="btn btn-success px-4 py-2 rounded-pill"><i class="fa-brands fa-whatsapp me-2"></i>Daftar via WhatsApp (085954447600)</a></div>',
            'meta_title' => 'PPDB 2026 - Pendaftaran Siswa Baru PKBM S.Supriadi',
            'meta_description' => 'Penerimaan Peserta Didik Baru PPDB 2026 Paket A, B, C di PKBM S.Supriadi Sukun Malang.',
            'status' => 'published',
            'sort_order' => 15,
            'comment_settings' => json_encode([
                'enabled' => false,
                'style' => 'cards',
                'allow_guests' => true,
                'moderation' => false,
                'per_page' => 10,
            ]),
        ],
        [
            'title' => 'Daftar Siswa Paket A',
            'slug' => 'paket-a',
            'page_type' => 'standard',
            'content' => '<h2>Daftar Siswa & Rombel Paket A (Setara SD)</h2><p class="text-muted">Jenjang pendidikan kesetaraan tingkat dasar (Kelas 1 sampai Kelas 6) PKBM S.Supriadi.</p><ul class="list-group list-group-flush shadow-sm border rounded"><li class="list-group-item">Kelas 1 Paket A</li><li class="list-group-item">Kelas 2 Paket A</li><li class="list-group-item">Kelas 3 Paket A</li><li class="list-group-item">Kelas 4 Paket A</li><li class="list-group-item">Kelas 5 Paket A</li><li class="list-group-item">Kelas 6 Paket A</li></ul>',
            'meta_title' => 'Daftar Siswa Paket A - PKBM S.Supriadi',
            'meta_description' => 'Informasi rombel dan siswa Paket A PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 16,
            'comment_settings' => json_encode(['enabled' => false, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 10]),
        ],
        [
            'title' => 'Daftar Siswa Paket B',
            'slug' => 'paket-b',
            'page_type' => 'standard',
            'content' => '<h2>Daftar Siswa & Rombel Paket B (Setara SMP)</h2><p class="text-muted">Jenjang pendidikan kesetaraan tingkat menengah pertama (Kelas 7, 8, 9) PKBM S.Supriadi.</p><ul class="list-group list-group-flush shadow-sm border rounded"><li class="list-group-item">Kelas 7 Paket B</li><li class="list-group-item">Kelas 8 Paket B</li><li class="list-group-item">Kelas 9 Paket B</li></ul>',
            'meta_title' => 'Daftar Siswa Paket B - PKBM S.Supriadi',
            'meta_description' => 'Informasi rombel dan siswa Paket B PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 17,
            'comment_settings' => json_encode(['enabled' => false, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 10]),
        ],
        [
            'title' => 'Daftar Siswa Paket C',
            'slug' => 'paket-c',
            'page_type' => 'standard',
            'content' => '<h2>Daftar Siswa & Rombel Paket C (Setara SMA)</h2><p class="text-muted">Jenjang pendidikan kesetaraan tingkat menengah atas (Kelas 10, 11, 12) PKBM S.Supriadi.</p><ul class="list-group list-group-flush shadow-sm border rounded"><li class="list-group-item">Kelas 10 Paket C</li><li class="list-group-item">Kelas 11 Paket C</li><li class="list-group-item">Kelas 12 Paket C</li></ul>',
            'meta_title' => 'Daftar Siswa Paket C - PKBM S.Supriadi',
            'meta_description' => 'Informasi rombel dan siswa Paket C PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 18,
            'comment_settings' => json_encode(['enabled' => false, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 10]),
        ],
        [
            'title' => 'Tentang Kami',
            'slug' => 'tentang-kami',
            'page_type' => 'standard',
            'content' => '<h2>Tentang PKBM S.Supriadi Kota Malang</h2><p>PKBM S.Supriadi adalah Pusat Kegiatan Belajar Masyarakat berizin resmi dari Dinas Pendidikan Kota Malang. Kami berkomitmen membuka akses pendidikan seluas-luasnya melalui kesetaraan Paket A, Paket B, dan Paket C dengan program vokasi praktis.</p>',
            'meta_title' => 'Tentang Kami - PKBM S.Supriadi',
            'meta_description' => 'Mengenal visi dan filosofi pendidikan inklusif PKBM S.Supriadi.',
            'status' => 'published',
            'sort_order' => 19,
            'comment_settings' => json_encode(['enabled' => false, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 10]),
        ],
        [
            'title' => 'Berita & Pengumuman',
            'slug' => 'berita',
            'page_type' => 'news_index',
            'content' => '<p class="lead text-muted">Dapatkan rilis berita, agenda akademik, dan liputan kegiatan terbaru dari PKBM S.Supriadi.</p>',
            'meta_title' => 'Warta & Berita Terkini - PKBM S.Supriadi',
            'meta_description' => 'Arsip berita, pengumuman, dan artikel edukasi terkini PKBM S.Supriadi Malang.',
            'status' => 'published',
            'sort_order' => 20,
            'comment_settings' => json_encode(['enabled' => false, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 10]),
        ],
        [
            'title' => 'Baca Berita',
            'slug' => 'baca-berita',
            'page_type' => 'news_single',
            'content' => null,
            'meta_title' => 'Detail Berita - PKBM S.Supriadi',
            'meta_description' => 'Halaman pembaca detail berita dengan interaksi komentar terintegrasi.',
            'status' => 'published',
            'sort_order' => 21,
            'comment_settings' => json_encode(['enabled' => true, 'style' => 'cards', 'allow_guests' => true, 'moderation' => false, 'per_page' => 15]),
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
        } else {
            $existingPage->update([
                'title' => $pData['title'],
                'page_type' => $pData['page_type'],
                'content' => $pData['content'],
                'meta_title' => $pData['meta_title'],
                'meta_description' => $pData['meta_description'],
                'status' => $pData['status'],
                'sort_order' => $pData['sort_order'],
                'comment_settings' => $pData['comment_settings'],
            ]);
            echo "[UPDATE] CMS Page updated: {$pData['title']} ({$pData['slug']})\n";
        }
    }

    // 9. Seed Initial News from pkbmsupriadi.sch.id
    $firstUser = User::findByEmail('admin@syntaxcore.com') ?? User::findByEmail('admin@gmail.com');
    $authorId = $firstUser ? $firstUser->id : 1;

    $sampleNews = [
        [
            'title' => 'Ceria SuperCamp at Winong Camp',
            'slug' => 'ceria-supercamp-at-winong-camp',
            'category_id' => $categoriesMap['artikel']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'Kegiatan Ceria SuperCamp siswa PKBM S. Supriadi yang dilaksanakan pada tanggal 29–30 April 2026 di Wana Wisata Winong berlangsung dengan sangat meriah, seru, dan penuh kebersamaan.',
            'content' => '<p>Kegiatan Ceria SuperCamp siswa PKBM S. Supriadi yang dilaksanakan pada tanggal 29–30 April 2026 di Wana Wisata Winong berlangsung dengan sangat meriah, seru, dan penuh kebersamaan. Kegiatan ini menjadi salah satu agenda edukatif dan rekreatif yang bertujuan untuk mempererat kekompakan antar siswa sekaligus melatih karakter positif melalui kegiatan di alam terbuka.</p><p>Sejak hari pertama, para siswa terlihat sangat antusias mengikuti seluruh rangkaian kegiatan yang telah disiapkan panitia. Berbagai aktivitas menarik seperti permainan kelompok, jelajah alam, pentas kebersamaan, api unggun, hingga kegiatan motivasi berhasil menciptakan suasana camp yang hangat dan menyenangkan.</p><p>Melalui kegiatan SuperCamp ini, siswa tidak hanya belajar tentang kemandirian dan kerja sama tim, tetapi juga diajak untuk lebih mencintai dan peduli terhadap kelestarian alam lingkungan sekitar.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/05/WhatsApp-Image-2026-05-06-at-20.55.03-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 45,
            'status' => 'published',
            'published_at' => '2026-05-06 09:00:00',
            'tags' => ['pkbm', 'supercamp', 'pendidikan'],
        ],
        [
            'title' => 'Pelaksanaan TKA Paket A',
            'slug' => 'pelaksanaan-tka-paket-a',
            'category_id' => $categoriesMap['ujian']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'Pelaksanaan Tes Kemampuan Akademik (TKA) Paket A Gelombang 3 di PKBM S. Supriadi berlangsung dengan tertib dan lancar.',
            'content' => '<p>Pelaksanaan Tes Kemampuan Akademik (TKA) Paket A Gelombang 3 di PKBM S. Supriadi berlangsung dengan tertib, lancar, dan penuh konsentrasi. Ujian ini diikuti oleh seluruh siswa Paket A yang telah mempersiapkan diri dengan baik melalui bimbingan para guru pengajar.</p><p>Kepala PKBM S. Supriadi menyampaikan apresiasi atas kedisiplinan para peserta didik dalam mengikuti ujian, serta berharap hasil yang dicapai dapat optimal dan memuaskan.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/05/WhatsApp-Image-2026-05-05-at-18.22.12-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 38,
            'status' => 'published',
            'published_at' => '2026-05-06 08:30:00',
            'tags' => ['pkbm', 'paket-a', 'tka', 'ujian'],
        ],
        [
            'title' => 'Belajar Mengenal Beragam Suku di Indonesia Melalui Metode Presentasi oleh Siswa Paket A',
            'slug' => 'belajar-mengenal-beragam-suku-di-indonesia-melalui-metode-presentasi-oleh-siswa-paket-a',
            'category_id' => $categoriesMap['berita-pendidikan']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'PKBM S. Supriadi terus mendorong inovasi metode belajar aktif melalui presentasi mengenal keberagaman suku bangsa di Indonesia.',
            'content' => '<p>PKBM S. Supriadi terus mendorong inovasi metode belajar aktif. Siswa Paket A diajak mengenal keberagaman suku, adat istiadat, dan budaya di nusantara melalui presentasi mandiri dan kelompok di hadapan teman sekelas.</p><p>Metode ini terbukti efektif meningkatkan keberanian berbicara di depan umum (public speaking) serta menumbuhkan rasa toleransi dan cinta tanah air sejak dini.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-14-at-22.04.02-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 29,
            'status' => 'published',
            'published_at' => '2026-04-14 10:00:00',
            'tags' => ['pkbm', 'paket-a', 'pendidikan'],
        ],
        [
            'title' => 'Pelaksanaan Ujian TKA Paket B',
            'slug' => 'pelaksanaan-ujian-tka-paket-b',
            'category_id' => $categoriesMap['ujian']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'Malang, 13–14 April 2026 — PKBM S. Supriadi telah melaksanakan kegiatan Tes Kemampuan Akademik (TKA) Paket B.',
            'content' => '<p>Malang, 13–14 April 2026 — PKBM S. Supriadi telah sukses menyelenggarakan kegiatan Tes Kemampuan Akademik (TKA) untuk jenjang Paket B (setara SMP). Pelaksanaan berjalan kondusif dengan pengawasan langsung dari tutor dan tim kurikulum sekolah.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-13-at-15.25.31-1-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 31,
            'status' => 'published',
            'published_at' => '2026-04-13 14:00:00',
            'tags' => ['pkbm', 'paket-b', 'tka', 'ujian'],
        ],
        [
            'title' => 'Pelaksanaan UPK Paket C',
            'slug' => 'pelaksanaan-upk-paket-c',
            'category_id' => $categoriesMap['ujian']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'Pelaksanaan Ujian Pendidikan Kesetaraan (UPK) Paket C di PKBM S. Supriadi Berjalan Lancar.',
            'content' => '<p>Pelaksanaan Ujian Pendidikan Kesetaraan (UPK) Paket C (setara SMA) di PKBM S. Supriadi berlangsung dengan tertib dan lancar. Ujian kesetaraan ini merupakan tahapan penentu kelulusan bagi warga belajar tingkat akhir yang hendak melanjutkan ke jenjang perguruan tinggi maupun meniti karir profesional.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/04/WhatsApp-Image-2026-04-05-at-13.52.13-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 42,
            'status' => 'published',
            'published_at' => '2026-04-05 11:00:00',
            'tags' => ['pkbm', 'paket-c', 'ujian'],
        ],
        [
            'title' => 'Gladi TKA Paket A dan Paket B',
            'slug' => 'gladi-tka-paket-a-dan-paket-b',
            'category_id' => $categoriesMap['ujian']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'PKBM S. Supriadi melaksanakan kegiatan gladi Tes Kemampuan Akademik (TKA) bagi peserta didik Paket A dan B.',
            'content' => '<p>PKBM S. Supriadi menyelenggarakan sesi gladi simulasi Tes Kemampuan Akademik (TKA) sebagai langkah pemantapan mental, teknis, dan pemahaman materi ujian bagi seluruh peserta didik Paket A dan Paket B.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-11-at-09.27.27-2-680x554.jpeg',
            'allow_comments' => 1,
            'views_count' => 22,
            'status' => 'published',
            'published_at' => '2026-03-11 09:00:00',
            'tags' => ['pkbm', 'tka', 'ujian'],
        ],
        [
            'title' => 'Pemanfaatan Laboratorium Komputer dan Teknologi Digital dalam Pembelajaran PKBM S. Supriadi',
            'slug' => 'pemanfaatan-laboratorium-komputer-teknologi-digital-dalam-pembelajaran',
            'category_id' => $categoriesMap['teknologi']?->id ?? null,
            'user_id' => $authorId,
            'summary' => 'PKBM S. Supriadi terus mengintegrasikan fasilitas laboratorium komputer modern dan platform pembelajaran digital untuk meningkatkan literasi teknologi warga belajar.',
            'content' => '<p>Di era transformasi digital, keterampilan teknologi informasi menjadi kebutuhan primer. PKBM S. Supriadi memfasilitasi warga belajar dengan laboratorium komputer lengkap dan akses pembelajaran mandiri berbasis web.</p><p>Melalui program ini, siswa Paket A, B, maupun C dilatih mengoperasikan aplikasi perkantoran, desain grafis dasar, serta pemanfaatan internet sehat dan produktif untuk menunjang kebutuhan dunia kerja masa kini.</p>',
            'featured_image' => 'https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_6-1070x900.jpg',
            'allow_comments' => 1,
            'views_count' => 28,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'tags' => ['teknologi', 'pkbm', 'pendidikan'],
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
            $existingNews->update([
                'title' => $nData['title'],
                'category_id' => $nData['category_id'],
                'summary' => $nData['summary'],
                'content' => $nData['content'],
                'featured_image' => $nData['featured_image'],
                'allow_comments' => $nData['allow_comments'],
                'status' => $nData['status'],
            ]);
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
                'author_name' => 'Muh Fajar Mujahid, S.Pd',
                'author_email' => 'pkbmsupriadi1@gmail.com',
                'content' => 'Alhamdulillah kegiatan SuperCamp di Wana Wisata Winong terlaksana dengan sukses dan penuh antusiasme dari seluruh warga belajar. Tetap semangat belajar!',
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
                'author_name' => 'Ismail',
                'author_email' => 'ismail.alumni@example.com',
                'content' => 'Seru sekali acaranya! Semoga adik-adik kelas semakin kompak dan berprestasi.',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (SyntaxCore Seeder)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ]);
            echo "[SUCCESS] Sample comments seeded for news '{$firstNews->title}'\n";
        }
    }

    // 11. Seed Public Menus (Exact tree from pkbmsupriadi.sch.id)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE public_menus; SET FOREIGN_KEY_CHECKS = 1;");

    $publicMenusData = [
        [
            'title' => 'Beranda',
            'link_type' => 'custom',
            'link_target' => null,
            'url' => '/',
            'sort_order' => 1,
            'children' => [],
        ],
        [
            'title' => 'Profil Kami',
            'link_type' => 'custom',
            'link_target' => null,
            'url' => '#',
            'sort_order' => 2,
            'children' => [
                [
                    'title' => 'Profil Sekolah',
                    'link_type' => 'page',
                    'link_target' => 'profil-sekolah',
                    'url' => '/page/profil-sekolah',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Identitas Sekolah',
                    'link_type' => 'page',
                    'link_target' => 'identitas-sekolah',
                    'url' => '/page/identitas-sekolah',
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Visi & Misi',
                    'link_type' => 'page',
                    'link_target' => 'visi-misi',
                    'url' => '/page/visi-misi',
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Sambutan Kepala Sekolah',
                    'link_type' => 'page',
                    'link_target' => 'sambutan-kepala-sekolah',
                    'url' => '/page/sambutan-kepala-sekolah',
                    'sort_order' => 4,
                ],
                [
                    'title' => 'Sejarah Singkat',
                    'link_type' => 'page',
                    'link_target' => 'sejarah-singkat',
                    'url' => '/page/sejarah-singkat',
                    'sort_order' => 5,
                ],
                [
                    'title' => 'Struktur Organisasi',
                    'link_type' => 'page',
                    'link_target' => 'struktur-organisasi',
                    'url' => '/page/struktur-organisasi',
                    'sort_order' => 6,
                ],
                [
                    'title' => 'Staf Pengajar',
                    'link_type' => 'page',
                    'link_target' => 'staf-pengajar',
                    'url' => '/page/staf-pengajar',
                    'sort_order' => 7,
                ],
                [
                    'title' => 'Staf Tenaga Kependidikan',
                    'link_type' => 'page',
                    'link_target' => 'staf-tenaga-kependidikan',
                    'url' => '/page/staf-tenaga-kependidikan',
                    'sort_order' => 8,
                ],
            ],
        ],
        [
            'title' => 'Fasilitas',
            'link_type' => 'page',
            'link_target' => 'fasilitas-sekolah',
            'url' => '/page/fasilitas-sekolah',
            'sort_order' => 3,
            'children' => [],
        ],
        [
            'title' => 'Galeri',
            'link_type' => 'page',
            'link_target' => 'galeri-sekolah',
            'url' => '/page/galeri-sekolah',
            'sort_order' => 4,
            'children' => [],
        ],
        [
            'title' => 'Informasi & Berita',
            'link_type' => 'page',
            'link_target' => 'berita',
            'url' => '/berita',
            'sort_order' => 5,
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
                [
                    'title' => 'Artikel',
                    'link_type' => 'news_category',
                    'link_target' => 'artikel',
                    'url' => null,
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Ujian',
                    'link_type' => 'news_category',
                    'link_target' => 'ujian',
                    'url' => null,
                    'sort_order' => 4,
                ],
            ],
        ],
        [
            'title' => 'Intrakurikuler',
            'link_type' => 'page',
            'link_target' => 'ekstrakurikuler',
            'url' => '/page/ekstrakurikuler',
            'sort_order' => 6,
            'children' => [],
        ],
        [
            'title' => 'Kontak',
            'link_type' => 'page',
            'link_target' => 'kontak',
            'url' => '/page/kontak',
            'sort_order' => 7,
            'children' => [],
        ],
        [
            'title' => 'Kelulusan',
            'link_type' => 'page',
            'link_target' => 'kelulusan',
            'url' => '/page/kelulusan',
            'sort_order' => 8,
            'children' => [],
        ],
        [
            'title' => 'Daftar Siswa',
            'link_type' => 'custom',
            'link_target' => null,
            'url' => '#',
            'sort_order' => 9,
            'children' => [
                [
                    'title' => 'Paket A (Setara SD)',
                    'link_type' => 'page',
                    'link_target' => 'paket-a',
                    'url' => '/page/paket-a',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Paket B (Setara SMP)',
                    'link_type' => 'page',
                    'link_target' => 'paket-b',
                    'url' => '/page/paket-b',
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Paket C (Setara SMA)',
                    'link_type' => 'page',
                    'link_target' => 'paket-c',
                    'url' => '/page/paket-c',
                    'sort_order' => 3,
                ],
            ],
        ],
        [
            'title' => 'Online Mandiri',
            'link_type' => 'custom',
            'link_target' => null,
            'url' => 'https://sites.google.com/view/pkbmssupriadionline-com/halaman-muka',
            'sort_order' => 10,
            'children' => [],
        ],
        [
            'title' => 'PPDB 2026',
            'link_type' => 'page',
            'link_target' => 'ppdb',
            'url' => '/page/ppdb',
            'sort_order' => 11,
            'children' => [],
        ],
    ];

    $seedPublicMenuRecursive = function (array $def, ?int $parentId = null) use (&$seedPublicMenuRecursive, $pdo) {
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

        if (!empty($def['children'])) {
            foreach ($def['children'] as $childDef) {
                $seedPublicMenuRecursive($childDef, $menuId);
            }
        }
    };

    foreach ($publicMenusData as $pMenu) {
        $seedPublicMenuRecursive($pMenu, null);
    }
    echo "[SUCCESS] All public menus seeded successfully!\n";
} catch (\Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
