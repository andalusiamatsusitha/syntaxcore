<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page->meta_title ?: $page->title) ?> - SyntaxCore</title>
    <?php if (!empty($page->meta_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($page->meta_description) ?>">
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

    <!-- Breadcrumb & Header Hero -->
    <div class="bg-white border-bottom py-4 mb-4">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted"><i class="fa-solid fa-house small me-1"></i>Beranda</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= htmlspecialchars($page->title) ?></li>
                </ol>
            </nav>
            <h1 class="h2 fw-bold text-dark mb-1"><?= htmlspecialchars($page->title) ?></h1>
        </div>
    </div>

    <!-- Page Content -->
    <main class="container mb-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white">
                    <div class="content-body text-dark" style="line-height: 1.8; font-size: 15.5px;">
                        <?= $page->content ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
