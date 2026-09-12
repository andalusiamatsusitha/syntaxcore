<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Login - SyntaxCore</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome Free 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- App CSS & Window CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/window.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            background-color: #0f172a;
            user-select: none;
        }

        #wd-workspace {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #wd-workspace-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-window {
            position: relative;
            z-index: 10;
            width: 380px;
            max-width: 92vw;
            border-radius: 8px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .wd-window-header {
            cursor: move;
            border-top-left-radius: 8px !important;
            border-top-right-radius: 8px !important;
        }
    </style>
</head>

<body>
    <!-- Workspace Desktop (Tanpa Header dan Footer) -->
    <main id="wd-workspace" class="wd-workspace">
        <!-- Layer Wallpaper Backdrop -->
        <div id="wd-workspace-backdrop"></div>

        <!-- Windows Login Dialog -->
        <div class="card wd-window login-window border-0" id="loginWindow">
            <!-- Window Titlebar -->
            <div class="wd-window-header card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom" id="windowHeader">
                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                    <i class="fa-solid fa-cube text-primary small"></i>
                    <span class="wd-window-title small fw-semibold text-truncate text-dark">Admin Login</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" title="Minimize" style="font-size: 11px; text-decoration: none;" tabindex="-1">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" title="Maximize" style="font-size: 11px; text-decoration: none;" tabindex="-1">
                        <i class="fa-regular fa-square"></i>
                    </button>
                    <a href="/" class="btn btn-sm btn-close border-0 p-0" aria-label="Close" style="font-size: 10px;" title="Kembali ke Website"></a>
                </div>
            </div>

            <!-- Window Body -->
            <div class="wd-window-body card-body p-4 bg-white" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                <!-- Desktop User Icon & Header -->
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-user-lock fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">SyntaxCore Desktop</h6>
                    <small class="text-muted" style="font-size: 12px;">Masukkan kredensial akun administrator</small>
                </div>

                <!-- Alert Kesalahan -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3 small d-flex align-items-center gap-2 border-danger-subtle" role="alert">
                        <i class="fa-solid fa-circle-exclamation text-danger flex-shrink-0"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Formulir Login -->
                <form action="/admin/login" method="POST" id="adminLoginForm" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Input Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-dark mb-1">Email</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email"
                                value="<?= htmlspecialchars($oldEmail ?? '') ?>" required autofocus
                                placeholder="admin@syntaxcore.com" autocomplete="email">
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-dark mb-1">Password</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control border-start-0 border-end-0" id="password"
                                name="password" required placeholder="••••••••" autocomplete="current-password">
                            <button type="button" class="btn btn-light border border-start-0 text-muted px-2"
                                id="btnTogglePassword" title="Lihat password">
                                <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Opsi Ingat & Demo -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label small text-muted" for="rememberMe" style="font-size: 12px;">Ingat sesi</label>
                        </div>
                        <a href="javascript:void(0)" onclick="fillDemoCredentials()" class="small text-decoration-none fw-semibold" style="font-size: 12px;">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Akun Demo
                        </a>
                    </div>

                    <!-- Tombol Submit -->
                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold shadow-sm" id="btnLoginSubmit">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Masuk
                    </button>
                </form>

                <!-- Link Kembali ke Website -->
                <div class="text-center mt-3 pt-3 border-top">
                    <a href="/" class="text-muted small text-decoration-none" style="font-size: 12px;">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Website
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>

    <script>
        // Memuat wallpaper yang tersimpan di desktop jika ada
        (function initWallpaper() {
            try {
                const saved = localStorage.getItem('syntaxcore_desktop_wallpaper');
                if (saved) {
                    const cfg = JSON.parse(saved);
                    const backdrop = document.getElementById('wd-workspace-backdrop');
                    if (backdrop) {
                        if (cfg.type === 'image' && cfg.url) {
                            backdrop.style.backgroundImage = 'url("' + cfg.url + '")';
                            backdrop.style.backgroundSize = cfg.mode || 'cover';
                            backdrop.style.backgroundPosition = 'center';
                        } else if (cfg.type === 'gradient' && cfg.gradient) {
                            backdrop.style.backgroundImage = cfg.gradient;
                        }
                    }
                }
            } catch (e) {
                console.warn('Gagal memuat wallpaper desktop:', e);
            }
        })();

        // Toggle Show / Hide Password
        document.getElementById('btnTogglePassword')?.addEventListener('click', function () {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (!pwd || !icon) return;

            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Quick Demo Fill
        function fillDemoCredentials() {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = 'admin@syntaxcore.com';
                passInput.value = 'secretpassword123';
                passInput.focus();
            }
        }

        // Loading state saat tombol submit ditekan
        document.getElementById('adminLoginForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('btnLoginSubmit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Masuk...';
            }
        });

        // Sederhana: Drag window login seperti desktop window sesungguhnya
        (function initDraggableWindow() {
            const win = document.getElementById('loginWindow');
            const header = document.getElementById('windowHeader');
            if (!win || !header) return;

            let isDragging = false;
            let startX = 0, startY = 0, initialLeft = 0, initialTop = 0;

            header.addEventListener('mousedown', function (e) {
                if (e.target.closest('button, a')) return;
                isDragging = true;
                const rect = win.getBoundingClientRect();
                startX = e.clientX;
                startY = e.clientY;
                initialLeft = rect.left;
                initialTop = rect.top;

                win.style.position = 'absolute';
                win.style.left = initialLeft + 'px';
                win.style.top = initialTop + 'px';
                win.style.margin = '0';

                document.body.style.userSelect = 'none';
            });

            document.addEventListener('mousemove', function (e) {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                win.style.left = Math.max(0, Math.min(window.innerWidth - win.offsetWidth, initialLeft + dx)) + 'px';
                win.style.top = Math.max(0, Math.min(window.innerHeight - win.offsetHeight, initialTop + dy)) + 'px';
            });

            document.addEventListener('mouseup', function () {
                isDragging = false;
                document.body.style.userSelect = '';
            });
        })();
    </script>
</body>

</html>
