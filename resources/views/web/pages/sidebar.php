<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" href="/favicon.png">
    <?php
        $pageTitle = $page->meta_title ?: $page->title;
        if (!str_contains($pageTitle, 'PKBM S.Supriadi')) {
            $pageTitle .= ' - PKBM S.Supriadi';
        }
    ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if (!empty($page->meta_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($page->meta_description) ?>">
    <?php endif; ?>
    <link rel="canonical" href="https://pkbmssupriadi.sch.id/page/<?= htmlspecialchars($page->slug) ?>">
    <!-- Open Graph (OG) Meta Tags - PKBM S.Supriadi -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page->meta_description ?: 'Informasi resmi dan profil kegiatan belajar mengajar PKBM S.Supriadi Kota Malang.') ?>">
    <meta property="og:image" content="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/logo1-pkbmsupriadi.sch_.id_.png">
    <meta property="og:url" content="https://pkbmssupriadi.sch.id/page/<?= htmlspecialchars($page->slug) ?>">
    <meta property="og:site_name" content="PKBM S.Supriadi">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- FontAwesome Free 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333333;
        }
        .bg-school-green { background-color: #2db700 !important; }
        .text-school-green { color: #2db700 !important; }
        .btn-school-green { background-color: #2db700; border-color: #2db700; color: #fff; }
        .btn-school-green:hover { background-color: #259a00; border-color: #259a00; color: #fff; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Shared Dynamic Navbar -->
    <?php include dirname(__DIR__) . '/layouts/navbar.php'; ?>

    <!-- Breadcrumb & Header Hero -->
    <div class="bg-white border-bottom py-4 mb-4 shadow-sm">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted"><i class="fa-solid fa-house small me-1"></i>Beranda</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= htmlspecialchars($page->title) ?></li>
                </ol>
            </nav>
            <h1 class="h2 fw-bold text-dark mb-0"><?= htmlspecialchars($page->title) ?></h1>
        </div>
    </div>

    <!-- Main Content & Sidebar Layout -->
    <main class="container mb-5 flex-grow-1">
        <div class="row g-4">
            <!-- Main Content Area -->
            <div class="col-12 col-lg-8">
                <article class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white mb-4">
                    <div class="content-body text-dark" style="line-height: 1.8; font-size: 15.5px;">
                        <?= $page->content ?>
                    </div>
                </article>
            </div>

            <!-- Sidebar Area -->
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-column gap-4 sticky-top" style="top: 80px; z-index: 10;">
                    <!-- Navigation Widget -->
                    <?php if (!empty($sidebarPages)): ?>
                        <div class="card shadow-sm border-0 rounded-3 p-3 bg-white">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                                <i class="fa-solid fa-book-open text-primary"></i>
                                <span>Halaman Terkait</span>
                            </h6>
                            <div class="list-group list-group-flush small">
                                <?php foreach ($sidebarPages as $sp): ?>
                                    <?php if ($sp->slug !== 'beranda'): ?>
                                        <a href="/<?= htmlspecialchars($sp->slug) ?>" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded-2 <?= $sp->slug === $page->slug ? 'bg-primary text-white fw-semibold' : 'text-dark' ?>">
                                            <i class="fa-solid fa-angle-right me-1 small <?= $sp->slug === $page->slug ? 'text-white' : 'text-muted' ?>"></i>
                                            <?= htmlspecialchars($sp->title) ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent News Widget -->
                    <?php if (!empty($recentNews)): ?>
                        <div class="card shadow-sm border-0 rounded-3 p-3 bg-white">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                                <i class="fa-solid fa-newspaper text-primary"></i>
                                <span>Warta Terkini</span>
                            </h6>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($recentNews as $rn): ?>
                                    <div class="d-flex gap-2 align-items-start">
                                        <?php if (!empty($rn->featured_image)): ?>
                                            <img src="<?= htmlspecialchars($rn->featured_image) ?>" alt="<?= htmlspecialchars($rn->title) ?>" class="rounded border flex-shrink-0" style="width: 52px; height: 52px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0 border" style="width: 52px; height: 52px;">
                                                <i class="fa-solid fa-newspaper"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="overflow-hidden">
                                            <a href="/berita/<?= htmlspecialchars($rn->slug) ?>" class="text-decoration-none text-dark fw-semibold small text-truncate-2 d-block mb-1">
                                                <?= htmlspecialchars($rn->title) ?>
                                            </a>
                                            <span class="text-muted" style="font-size: 11px;">
                                                <i class="fa-regular fa-clock me-1"></i><?= date('d M Y', strtotime($rn->created_at ?? 'now')) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Support Card -->
                    <div class="card shadow-sm border-0 rounded-3 p-3 bg-primary text-white text-center">
                        <div class="py-2">
                            <i class="fa-solid fa-headset fa-2x mb-2 opacity-75"></i>
                            <h6 class="fw-bold mb-1">Pusat Bantuan & Kontak</h6>
                            <p class="small opacity-75 mb-3">Punya pertanyaan atau butuh bantuan lebih lanjut?</p>
                            <a href="/hubungi-kami" class="btn btn-light btn-sm text-primary fw-semibold px-3 shadow-sm">
                                Hubungi Tim Kami
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
