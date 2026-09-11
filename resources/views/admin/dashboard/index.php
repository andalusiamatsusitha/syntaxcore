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

<body style="margin: 0; height: 100vh; overflow: hidden;">
    <!-- Logged in user: <?= htmlspecialchars($user?->name ?? 'Admin') ?> -->
    
    <!-- Div yang menampung semua konten dinamis (header, body/workspace, dan footer) -->
    <div id="wd-content" style="height: 100%; width: 100%;">
        <!-- Konten akan dirender secara dinamis oleh JavaScript WindowCore -->
        <noscript>
            <div class="p-4 text-center">
                <h3>JavaScript Diperlukan</h3>
                <p>Silakan aktifkan JavaScript di browser Anda untuk membuka dashboard.</p>
            </div>
        </noscript>
    </div>

    <!-- Hidden CSRF token element -->
    <div style="display: none;"><?= csrf_field() ?></div>

    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- Window Core JS -->
    <script src="/assets/js/window/core.js" defer></script>
    <!-- SyntaxCore App JS -->
    <script src="/assets/js/app.js" defer></script>

    <!-- Inisialisasi Window Client -->
    <script>
        (function () {
            window.adminApp = new Core("init", {
                userName: "<?= htmlspecialchars($user?->name ?? 'Administrator') ?>",
                userEmail: "<?= htmlspecialchars($user?->email ?? '') ?>"
            });
        })();
    </script>
</body>

</html>
