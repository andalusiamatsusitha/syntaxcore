<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SyntaxCore') ?> - Elegant PHP Framework</title>
</head>

<body>
    <div class="container">
        <div class="badge">SyntaxCore v<?= htmlspecialchars($version ?? '1.0.0') ?></div>
        <h1><?= htmlspecialchars($appName ?? 'SyntaxCore') ?></h1>

        <div class="footer">
            PHP Version: <span><?= htmlspecialchars($phpVersion ?? PHP_VERSION) ?></span>
        </div>
    </div>
</body>

</html>
