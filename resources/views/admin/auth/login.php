<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Login - SyntaxCore</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background-color: #f1f5f9;">
    <div class="card shadow-sm border-0 p-4" style="max-width: 420px; width: 100%;">
        <div class="card-body">
            <div class="text-center mb-4">
                <span class="badge bg-primary mb-2">SyntaxCore Admin</span>
                <h1 class="h4 fw-bold">Sign In</h1>
                <p class="text-muted small">Enter your credentials to access the panel</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/admin/login" method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?= htmlspecialchars($oldEmail ?? '') ?>" required autofocus placeholder="admin@example.com">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- App JS -->
    <script src="/assets/js/app.js" defer></script>
</body>

</html>
