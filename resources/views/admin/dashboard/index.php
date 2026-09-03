<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Dashboard - SyntaxCore</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-3 shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold mb-0">SyntaxCore Admin</span>

            <div class="d-flex align-items-center">
                <span class="text-light small me-3">
                    Logged in as: <strong><?= htmlspecialchars($user?->name ?? 'Admin') ?></strong>
                    (<?= htmlspecialchars($user?->email ?? '') ?>)
                </span>

                <form action="/admin/logout" method="POST" class="d-inline m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h1 class="h4 fw-bold mb-2">Welcome to Admin Dashboard</h1>
                        <p class="text-muted mb-4">You are securely authenticated into the administrative panel.</p>

                        <div class="p-3 bg-light rounded">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="text-muted small">Status</div>
                                    <div class="fw-semibold text-success">Authenticated</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">User ID</div>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($user?->id ?? '')) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Framework</div>
                                    <div class="fw-semibold text-dark">SyntaxCore MVC</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- App JS -->
    <script src="/assets/js/app.js" defer></script>
</body>

</html>
