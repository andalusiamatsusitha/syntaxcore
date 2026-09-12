<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activeCategory ? 'Kategori: ' . $activeCategory->name : ($activeTag ? 'Tag: #' . $activeTag->name : ($page->meta_title ?: 'Warta & Berita Terkini'))) ?> - PKBM S.Supriadi</title>
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

    <!-- News Index Hero Header -->
    <section class="bg-white border-bottom py-5 mb-4">
        <div class="container">
            <div class="row align-items-center justify-content-between g-4">
                <div class="col-12 col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 small">
                            <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted"><i class="fa-solid fa-house small me-1"></i>Beranda</a></li>
                            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Warta & Berita</li>
                        </ol>
                    </nav>
                    <h1 class="h2 fw-bold text-dark mb-2">
                        <?php if ($activeCategory): ?>
                            Kategori: <span class="text-primary"><?= htmlspecialchars($activeCategory->name) ?></span>
                        <?php elseif ($activeTag): ?>
                            Tag: <span class="text-success">#<?= htmlspecialchars($activeTag->name) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($page->title ?? 'Warta & Berita Terkini') ?>
                        <?php endif; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?= $activeCategory ? htmlspecialchars($activeCategory->description ?: 'Semua artikel dalam kategori ini.') : ($page->content ? strip_tags($page->content) : 'Pembaruan rilis framework, artikel rekayasa perangkat lunak, dan wawasan teknologi.') ?>
                    </p>
                </div>

                <!-- Search Box -->
                <div class="col-12 col-md-5 col-lg-4">
                    <form action="/berita" method="GET" class="d-flex gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari judul berita..." value="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off">
                            <button class="btn btn-primary" type="submit">Cari</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Taxonomy Filter Pills -->
            <div class="d-flex align-items-center gap-2 flex-wrap mt-4 pt-3 border-top">
                <span class="small fw-semibold text-muted me-1"><i class="fa-solid fa-filter me-1"></i>Kategori:</span>
                <a href="/berita" class="badge rounded-pill text-decoration-none <?= (!$activeCategory && !$activeTag) ? 'bg-primary text-white' : 'bg-light text-dark border' ?> px-3 py-2">
                    Semua
                </a>
                <?php foreach ($categories as $cat): ?>
                    <?php $isAct = ($activeCategory && $activeCategory->id === $cat->id); ?>
                    <a href="/berita/kategori/<?= htmlspecialchars($cat->slug) ?>" class="badge rounded-pill text-decoration-none px-3 py-2 <?= $isAct ? 'text-white shadow-sm' : 'bg-white text-dark border' ?>" style="<?= $isAct ? "background-color: {$cat->color};" : '' ?>">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: <?= $cat->color ?>;"></span>
                        <?= htmlspecialchars($cat->name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- News Grid Cards -->
    <main class="container mb-5 flex-grow-1">
        <?php if (empty($newsList)): ?>
            <div class="card shadow-sm border-0 rounded-3 p-5 text-center bg-white my-4">
                <div class="py-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-newspaper fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Belum Ada Berita Ditemukan</h5>
                    <p class="text-muted small mb-3">Tidak ada artikel yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                    <a href="/berita" class="btn btn-sm btn-outline-primary">Reset Filter & Tampilkan Semua</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($newsList as $item): ?>
                    <?php
                        $cat = $item->category();
                        $author = $item->author();
                        $tags = $item->tags();
                        $commentCount = $item->approvedCommentsCount();
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden d-flex flex-column bg-white hover-shadow transition">
                            <!-- Thumbnail -->
                            <div class="position-relative bg-light" style="height: 190px;">
                                <?php if (!empty($item->featured_image)): ?>
                                    <img src="<?= htmlspecialchars($item->featured_image) ?>" alt="<?= htmlspecialchars($item->title) ?>" class="w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-muted">
                                        <i class="fa-solid fa-image fs-1 text-secondary opacity-25"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Category Badge Overlay -->
                                <?php if ($cat): ?>
                                    <div class="position-absolute top-0 start-0 m-3">
                                        <span class="badge shadow-sm" style="background-color: <?= htmlspecialchars($cat->color ?: '#0d6efd') ?>;">
                                            <?= htmlspecialchars($cat->name) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center gap-2 small text-muted mb-2 font-monospace" style="font-size: 11.5px;">
                                    <span><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($item->published_at ?: $item->created_at)) ?></span>
                                    <span>&bull;</span>
                                    <span><i class="fa-regular fa-eye me-1"></i><?= $item->views_count ?> views</span>
                                </div>

                                <h5 class="card-title fw-bold mb-2">
                                    <a href="/berita/<?= htmlspecialchars($item->slug) ?>" class="text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($item->title) ?>
                                    </a>
                                </h5>

                                <p class="card-text text-muted small flex-grow-1" style="line-height: 1.6;">
                                    <?= htmlspecialchars(mb_substr($item->summary ?: strip_tags($item->content), 0, 120)) ?>...
                                </p>

                                <!-- Card Footer -->
                                <div class="pt-3 mt-3 border-top d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2 small text-muted">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold" style="width: 24px; height: 24px; font-size: 10px;">
                                            <?= strtoupper(substr($author?->name ?? 'A', 0, 1)) ?>
                                        </div>
                                        <span class="text-truncate" style="max-width: 100px;"><?= htmlspecialchars($author?->name ?? 'Admin') ?></span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-muted border small">
                                            <i class="fa-solid fa-comments me-1"></i><?= $commentCount ?>
                                        </span>
                                        <a href="/berita/<?= htmlspecialchars($item->slug) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Baca Selengkapnya">
                                            <i class="fa-solid fa-arrow-right small"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-5 d-flex justify-content-center" aria-label="Navigasi Halaman Berita">
                    <ul class="pagination shadow-sm">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($activeCategory?->slug ?? '') ?>&tag=<?= urlencode($activeTag?->slug ?? '') ?>">
                                <i class="fa-solid fa-chevron-left small me-1"></i> Sebelumnya
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($activeCategory?->slug ?? '') ?>&tag=<?= urlencode($activeTag?->slug ?? '') ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($activeCategory?->slug ?? '') ?>&tag=<?= urlencode($activeTag?->slug ?? '') ?>">
                                Selanjutnya <i class="fa-solid fa-chevron-right small ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
