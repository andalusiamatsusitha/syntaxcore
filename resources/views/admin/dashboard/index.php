<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Dashboard - SyntaxCore</title>
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body style="margin: 0; height: 100vh;">
    <!-- div yang menampung semua kontent dinamis ( header, body dan footer ) -->
    <div id="wd-content" style="height: 100%; width: 100%; background: red;">
        
    </div>
       
    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- App JS -->
    <script src="/assets/js/window/core.js" defer></script>
    <script src="/assets/js/app.js" defer></script>
    <script>
        (function () {
            new Core("init") ;
        });
    </script>
</body>

</html>
