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
<body class="bg-white d-flex flex-column min-vh-100">

    <!-- Shared Dynamic Navbar -->
    <?php include dirname(__DIR__) . '/layouts/navbar.php'; ?>

    <!-- Full-Width Canvas Main Container -->
    <main class="w-100 flex-grow-1">
        <div class="content-body">
            <?= $page->content ?>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
