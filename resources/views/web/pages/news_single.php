<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($news->title) ?> - PKBM S.Supriadi</title>
    <?php if (!empty($news->summary)): ?>
        <meta name="description" content="<?= htmlspecialchars(strip_tags($news->summary)) ?>">
    <?php endif; ?>
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

    <?php
        $category = $news->category();
        $author = $news->author();
        $tags = $news->tags();
        $layoutStyle = $commentSettings['style'] ?? 'cards';
        $commentsEnabled = !empty($commentSettings['enabled']) && !empty($news->allow_comments);
        $allowGuests = !empty($commentSettings['allow_guests']);
    ?>

    <!-- Breadcrumb & Header Hero -->
    <div class="bg-white border-bottom py-4 mb-4">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted"><i class="fa-solid fa-house small me-1"></i>Beranda</a></li>
                    <li class="breadcrumb-item"><a href="/berita" class="text-decoration-none text-muted">Berita</a></li>
                    <?php if ($category): ?>
                        <li class="breadcrumb-item"><a href="/berita/kategori/<?= htmlspecialchars($category->slug) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($category->name) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active text-dark fw-semibold text-truncate" style="max-width: 280px;" aria-current="page"><?= htmlspecialchars($news->title) ?></li>
                </ol>
            </nav>

            <h1 class="h2 fw-bold text-dark mb-3" style="line-height: 1.3;"><?= htmlspecialchars($news->title) ?></h1>

            <!-- Meta Details -->
            <div class="d-flex align-items-center gap-3 flex-wrap small text-muted font-monospace">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 11px;">
                        <?= strtoupper(substr($author?->name ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="text-dark fw-semibold font-sans-serif"><?= htmlspecialchars($author?->name ?? 'Administrator') ?></span>
                </div>
                <span>&bull;</span>
                <span><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y, H:i', strtotime($news->published_at ?: $news->created_at)) ?> WIB</span>
                <span>&bull;</span>
                <span><i class="fa-regular fa-eye me-1"></i><?= $news->views_count ?> Pembaca</span>
                <?php if ($category): ?>
                    <span>&bull;</span>
                    <a href="/berita/kategori/<?= htmlspecialchars($category->slug) ?>" class="badge text-decoration-none" style="background-color: <?= htmlspecialchars($category->color ?: '#0d6efd') ?>;">
                        <?= htmlspecialchars($category->name) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Article Body -->
    <main class="container mb-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <!-- Article Container -->
                <article class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white mb-5">
                    <?php if (!empty($news->featured_image)): ?>
                        <div class="mb-4 rounded-3 overflow-hidden border">
                            <img src="<?= htmlspecialchars($news->featured_image) ?>" alt="<?= htmlspecialchars($news->title) ?>" class="w-100" style="max-height: 460px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($news->summary)): ?>
                        <div class="lead text-muted mb-4 pb-3 border-bottom fst-italic" style="line-height: 1.7; font-size: 16.5px;">
                            <?= htmlspecialchars($news->summary) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Content Body -->
                    <div class="article-content text-dark mb-4" style="line-height: 1.9; font-size: 16px;">
                        <?= $news->content ?>
                    </div>

                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                        <div class="pt-4 mt-4 border-top d-flex align-items-center gap-2 flex-wrap">
                            <span class="small fw-semibold text-muted"><i class="fa-solid fa-tags me-1 text-success"></i>Tags:</span>
                            <?php foreach ($tags as $t): ?>
                                <a href="/berita/tag/<?= htmlspecialchars($t->slug) ?>" class="badge bg-light text-dark border text-decoration-none px-2 py-1">
                                    #<?= htmlspecialchars($t->name) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <!-- ======================================================= -->
                <!-- AREA KOMENTAR DINAMIS (KUSTOMISASI HALAMAN) -->
                <!-- ======================================================= -->
                <section id="comments" class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-comments text-primary fs-4"></i>
                            <h4 class="fw-bold mb-0 text-dark">Diskusi & Komentar</h4>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1">
                                <?= count($comments) ?>
                            </span>
                        </div>
                        <div class="small text-muted font-monospace">
                            Gaya: <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($layoutStyle) ?></span>
                        </div>
                    </div>

                    <!-- Flash Message Notifikasi -->
                    <?php if (isset($_SESSION['comment_flash'])): ?>
                        <?php $flash = $_SESSION['comment_flash']; unset($_SESSION['comment_flash']); ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> alert-dismissible fade show mb-4 py-2 small" role="alert">
                            <i class="fa-solid fa-circle-info me-2"></i><?= htmlspecialchars($flash['message'] ?? '') ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$commentsEnabled): ?>
                        <!-- Komentar dinonaktifkan -->
                        <div class="alert alert-light border text-center py-4 text-muted small my-3">
                            <i class="fa-solid fa-comment-slash me-2"></i>Kolom komentar pada halaman ini sedang dinonaktifkan.
                        </div>
                    <?php else: ?>

                        <!-- 1. DAFTAR KOMENTAR SESUAI GAYA TATA LETAK -->
                        <div class="comments-stream mb-5">
                            <?php if (empty($comments)): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="fa-regular fa-comment-dots fs-3 d-block mb-2 opacity-50"></i>
                                    Belum ada komentar pada artikel ini. Jadilah yang pertama memberikan tanggapan!
                                </div>
                            <?php else: ?>

                                <?php
                                    // Pisahkan root comments dan replies
                                    $rootComments = [];
                                    $repliesByParent = [];
                                    foreach ($comments as $c) {
                                        if (empty($c->parent_id)) {
                                            $rootComments[] = $c;
                                        } else {
                                            $repliesByParent[$c->parent_id][] = $c;
                                        }
                                    }
                                ?>

                                <?php if ($layoutStyle === 'cards'): ?>
                                    <!-- GAYA CARDS (Kartu Modern Berjarak) -->
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($rootComments as $c): ?>
                                            <div class="card border rounded-3 p-3 bg-light-subtle shadow-xs">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold small" style="width: 32px; height: 32px;">
                                                            <?= strtoupper(substr($c->author_name, 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($c->author_name) ?></span>
                                                            <?= $c->user_id ? '<span class="badge bg-info-subtle text-info border ms-1" style="font-size: 10px;">Member</span>' : '' ?>
                                                        </div>
                                                    </div>
                                                    <span class="text-muted small" style="font-size: 11.5px;"><?= date('d M Y, H:i', strtotime($c->created_at)) ?></span>
                                                </div>
                                                <div class="text-dark small ps-4" style="line-height: 1.6;">
                                                    <?= nl2br($c->content) ?>
                                                </div>

                                                <!-- Balasan (Replies) -->
                                                <?php if (!empty($repliesByParent[$c->id])): ?>
                                                    <div class="mt-3 pt-2 border-top ps-3 ms-3 d-flex flex-column gap-2" style="border-left: 2px solid #0d6efd !important;">
                                                        <?php foreach ($repliesByParent[$c->id] as $rep): ?>
                                                            <div class="p-2 bg-white rounded border small">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <div class="d-flex align-items-center gap-1">
                                                                        <strong class="text-dark"><?= htmlspecialchars($rep->author_name) ?></strong>
                                                                        <?= $rep->user_id ? '<span class="badge bg-primary-subtle text-primary border" style="font-size: 9px;">Admin</span>' : '' ?>
                                                                    </div>
                                                                    <span class="text-muted font-monospace" style="font-size: 10.5px;"><?= date('d/m/Y H:i', strtotime($rep->created_at)) ?></span>
                                                                </div>
                                                                <div class="text-secondary"><?= nl2br($rep->content) ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($layoutStyle === 'threaded'): ?>
                                    <!-- GAYA THREADED (Hierarki Bersarang dengan Garis Pohon) -->
                                    <div class="threaded-comments">
                                        <?php foreach ($rootComments as $c): ?>
                                            <div class="mb-4 pb-3 border-bottom">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-semibold small" style="width: 28px; height: 28px; font-size: 11px;">
                                                        <?= strtoupper(substr($c->author_name, 0, 1)) ?>
                                                    </div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($c->author_name) ?></span>
                                                    <span class="text-muted small">&bull; <?= date('d M Y, H:i', strtotime($c->created_at)) ?></span>
                                                </div>
                                                <div class="ps-4 text-dark small" style="line-height: 1.6;">
                                                    <?= nl2br($c->content) ?>
                                                </div>

                                                <!-- Nested Replies -->
                                                <?php if (!empty($repliesByParent[$c->id])): ?>
                                                    <div class="ms-4 ps-3 mt-3 border-start border-2 border-primary d-flex flex-column gap-2">
                                                        <?php foreach ($repliesByParent[$c->id] as $rep): ?>
                                                            <div class="bg-light p-2 rounded">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <strong class="text-primary small"><?= htmlspecialchars($rep->author_name) ?></strong>
                                                                    <span class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($rep->created_at)) ?></span>
                                                                </div>
                                                                <div class="small text-dark"><?= nl2br($rep->content) ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <!-- GAYA MINIMAL (Baris Ramping Padat) -->
                                    <ul class="list-group list-group-flush border-top border-bottom">
                                        <?php foreach ($comments as $c): ?>
                                            <li class="list-group-item px-0 py-2 small bg-transparent">
                                                <div class="d-flex justify-content-between align-items-baseline">
                                                    <span class="fw-semibold text-dark">
                                                        <?= $c->parent_id ? '<i class="fa-solid fa-reply text-muted small me-1"></i>' : '' ?>
                                                        <?= htmlspecialchars($c->author_name) ?>
                                                    </span>
                                                    <span class="text-muted font-monospace" style="font-size: 11px;"><?= date('d/m/Y H:i', strtotime($c->created_at)) ?></span>
                                                </div>
                                                <div class="text-secondary mt-1"><?= nl2br($c->content) ?></div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                            <?php endif; ?>
                        </div>

                        <!-- 2. FORM SUBMIT KOMENTAR -->
                        <div class="card border rounded-3 p-4 bg-light">
                            <h5 class="fw-bold text-dark mb-3 small text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen-nib me-1 text-primary"></i> Tinggalkan Komentar
                            </h5>

                            <?php if (!$user && !$allowGuests): ?>
                                <div class="alert alert-warning small mb-0">
                                    <i class="fa-solid fa-lock me-2"></i>Halaman ini hanya mengizinkan anggota terdaftar untuk mengirim komentar. Silakan <a href="/admin/login" class="fw-bold text-dark">masuk terlebih dahulu</a>.
                                </div>
                            <?php else: ?>
                                <form action="/news/<?= $news->id ?>/comments" method="POST" autocomplete="off">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="news_id" value="<?= $news->id ?>">

                                    <!-- Honeypot Anti-Spam Field (harus tetap kosong) -->
                                    <div style="display: none;">
                                        <input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off">
                                    </div>

                                    <?php if ($user): ?>
                                        <div class="d-flex align-items-center gap-2 mb-3 p-2 bg-white rounded border small">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 11px;">
                                                <?= strtoupper(substr($user->name, 0, 1)) ?>
                                            </div>
                                            <div>
                                                Berkomentar sebagai: <strong><?= htmlspecialchars($user->name) ?></strong> <span class="badge bg-success-subtle text-success border">Terverifikasi</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-2 mb-3">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small fw-semibold mb-1 text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" name="author_name" class="form-control form-control-sm" placeholder="Contoh: Andi Pratama" required>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small fw-semibold mb-1 text-secondary">Alamat Email <span class="text-danger">*</span></label>
                                                <input type="email" name="author_email" class="form-control form-control-sm" placeholder="nama@domain.com" required>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold mb-1 text-secondary">Tanggapan / Komentar <span class="text-danger">*</span></label>
                                        <textarea name="content" class="form-control form-control-sm" rows="3" placeholder="Tulis komentar atau pertanyaan Anda dengan sopan..." required minlength="3"></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <span class="small text-muted" style="font-size: 11.5px;">
                                            <i class="fa-solid fa-shield-halved text-success me-1"></i>Dilindungi CSRF & Anti-Spam Filter
                                        </span>
                                        <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Kirim Komentar
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
