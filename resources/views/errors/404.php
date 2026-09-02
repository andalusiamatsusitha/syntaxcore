<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; height: 100vh; align-items: center; justify-content: center; margin: 0; text-align: center; }
        .box { padding: 2rem; }
        h1 { font-size: 6rem; margin: 0; color: #38bdf8; font-weight: 800; line-height: 1; }
        p { font-size: 1.25rem; color: #94a3b8; margin-top: 1rem; }
        a { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #38bdf8; color: #0f172a; font-weight: 600; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404</h1>
        <p><?= htmlspecialchars($message ?? 'Halaman yang Anda cari tidak ditemukan.') ?></p>
        <a href="/">Kembali ke Beranda</a>
    </div>
</body>
</html>
