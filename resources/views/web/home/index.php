<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SyntaxCore') ?> - Elegant PHP Framework</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body class="d-flex align-items-center justify-content-center">
    <div class="card shadow-sm border-0 p-4 text-center" style="max-width: 480px; width: 100%;">
        <div class="card-body">
            <span class="badge bg-primary mb-3">SyntaxCore v<?= htmlspecialchars($version ?? '1.0.0') ?></span>
            <h1 class="h3 fw-bold mb-2"><?= htmlspecialchars($appName ?? 'SyntaxCore') ?></h1>
            <p class="text-muted mb-4">Lightweight & Elegant PHP MVC Framework</p>

            <div class="p-3 bg-light rounded text-muted small">
                PHP Version: <span class="fw-semibold text-dark"><?= htmlspecialchars($phpVersion ?? PHP_VERSION) ?></span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- App JS -->
    <script src="/assets/js/app.js" defer></script>
</body>

</html>
