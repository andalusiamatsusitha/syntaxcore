<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page->meta_title ?: $page->title) ?> - PKBM S.Supriadi</title>
    <?php if (!empty($page->meta_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($page->meta_description) ?>">
    <?php endif; ?>
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
<body class="bg-white d-flex flex-column min-vh-100">

    <!-- Shared Dynamic Navbar -->
    <?php include dirname(__DIR__) . '/layouts/navbar.php'; ?>

    <!-- Minimalist Clean Main Content -->
    <main class="container py-4 my-2 flex-grow-1">
        <div class="content-body text-dark" style="line-height: 1.8; font-size: 15.5px;">
            <?= $page->content ?>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
