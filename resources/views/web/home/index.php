<?php
$page = $page ?? null;
$menus = $menus ?? [];
$recentNews = $recentNews ?? [];
$version = $version ?? '1.0.0';
$phpVersion = $phpVersion ?? PHP_VERSION;
$appName = $appName ?? 'SyntaxCore';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SyntaxCore') ?> - Elegant PHP Framework</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Shared Dynamic Navbar -->
    <?php include dirname(__DIR__) . '/layouts/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="bg-white border-bottom py-5">
        <div class="container py-3">
            <div class="row align-items-center g-5">
                <div class="col-12 col-lg-7">
                    <span class="badge bg-primary mb-3 px-3 py-2">SyntaxCore v<?= htmlspecialchars($version ?? '1.0.0') ?></span>
                    <h1 class="display-5 fw-bold text-dark mb-3"><?= htmlspecialchars($appName ?? 'SyntaxCore') ?></h1>
                    <p class="lead text-muted mb-4">Lightweight & Elegant PHP MVC Framework</p>

                    <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
                        <a href="/berita" class="btn btn-primary px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-newspaper"></i> Warta & Berita
                        </a>
                        <a href="/tentang-kami" class="btn btn-outline-secondary px-4 py-2 d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-info"></i> Tentang Framework
                        </a>
                        <a href="/admin" class="btn btn-dark px-4 py-2 d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-display"></i> Desktop Admin
                        </a>
                    </div>

                    <div class="p-3 bg-light rounded text-muted small d-inline-block border">
                        PHP Version: <span class="fw-semibold text-dark"><?= htmlspecialchars($phpVersion ?? PHP_VERSION) ?></span>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card shadow border-0 rounded-4 p-4 bg-gradient text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Public CMS Engine</span>
                                <i class="fa-solid fa-layer-group text-white-50"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Arsitektur Modular</h4>
                            <p class="text-white-50 small mb-4" style="line-height: 1.7;">
                                Platform ini memisahkan entitas konten publik dan konfigurasi penyajian komentar, memberikan kendali penuh pada setiap halaman custom.
                            </p>
                            <div class="row g-2 text-center text-white small font-monospace">
                                <div class="col-4">
                                    <div class="p-2 rounded bg-white bg-opacity-10 border border-white border-opacity-10">
                                        <div class="fw-bold fs-6">Custom</div>
                                        <div class="text-white-50" style="font-size: 10px;">Pages</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-white bg-opacity-10 border border-white border-opacity-10">
                                        <div class="fw-bold fs-6">News</div>
                                        <div class="text-white-50" style="font-size: 10px;">Taxonomy</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-white bg-opacity-10 border border-white border-opacity-10">
                                        <div class="fw-bold fs-6">Dynamic</div>
                                        <div class="text-white-50" style="font-size: 10px;">Comments</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dynamic Content from Beranda Page (if seeded) -->
    <?php if ($page && !empty($page->content)): ?>
        <section class="container py-5">
            <div class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white">
                <div class="content-body text-dark" style="line-height: 1.8; font-size: 15.5px;">
                    <?= $page->content ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Recent News Section -->
    <?php if (!empty($recentNews)): ?>
        <section class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Berita & Wawasan Terkini</h3>
                    <p class="text-muted small mb-0">Rilis pembaruan, tutorial, dan dokumentasi langsung dari pengembang.</p>
                </div>
                <a href="/berita" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    Lihat Semua Berita <i class="fa-solid fa-arrow-right small"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($recentNews as $n): ?>
                    <?php
                        $nCat = $n->category();
                        $nAuthor = $n->author();
                        $nComments = $n->approvedCommentsCount();
                    ?>
                    <div class="col-12 col-md-4">
                        <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden d-flex flex-column bg-white">
                            <div class="position-relative bg-light" style="height: 160px;">
                                <?php if (!empty($n->featured_image)): ?>
                                    <img src="<?= htmlspecialchars($n->featured_image) ?>" alt="<?= htmlspecialchars($n->title) ?>" class="w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-muted">
                                        <i class="fa-solid fa-newspaper fs-2 text-secondary opacity-25"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ($nCat): ?>
                                    <div class="position-absolute top-0 start-0 m-3">
                                        <span class="badge shadow-sm" style="background-color: <?= htmlspecialchars($nCat->color ?: '#0d6efd') ?>;">
                                            <?= htmlspecialchars($nCat->name) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <span class="small text-muted mb-2 font-monospace" style="font-size: 11px;">
                                    <i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($n->published_at ?: $n->created_at)) ?>
                                </span>
                                <h5 class="card-title fw-bold mb-2">
                                    <a href="/berita/<?= htmlspecialchars($n->slug) ?>" class="text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($n->title) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1" style="line-height: 1.6;">
                                    <?= htmlspecialchars(mb_substr($n->summary ?: strip_tags($n->content), 0, 100)) ?>...
                                </p>
                                <div class="pt-3 mt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="fa-solid fa-comments me-1 text-primary"></i><?= $nComments ?> Diskusi</span>
                                    <a href="/berita/<?= htmlspecialchars($n->slug) ?>" class="fw-semibold text-primary text-decoration-none">
                                        Baca <i class="fa-solid fa-arrow-right small ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Shared Dynamic Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
