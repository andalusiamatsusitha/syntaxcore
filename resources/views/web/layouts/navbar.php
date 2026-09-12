<?php
/**
 * Shared Public Website Navbar - PKBM S.Supriadi
 *
 * @var array $menus Hierarchical tree from PublicMenu::tree(true)
 * @var string $activeMenu Current active menu route (e.g. '/', '/berita', '/page/profil-sekolah')
 */
$menus = $menus ?? \App\Models\PublicMenu::tree(true);
$activeMenu = $activeMenu ?? '/';
?>
<!-- Top Contact & Social Bar -->
<div class="bg-white border-bottom py-1 text-secondary d-none d-lg-block" style="font-size: 12.5px;">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- Contact Info -->
        <div class="d-flex align-items-center gap-4">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-phone text-success"></i>
                <span>085954447600</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-envelope text-success"></i>
                <a href="mailto:pkbmsupriadi1@gmail.com" class="text-secondary text-decoration-none hover-success">pkbmsupriadi1@gmail.com</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-certificate text-success"></i>
                NPSN P9962749 <span class="badge bg-success-subtle text-success border border-success-subtle">Terakreditasi</span>
            </div>
        </div>

        <!-- Social Media & Admin Desktop Quick Access -->
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 me-2">
                <a href="https://wa.me/6285954447600" target="_blank" class="text-secondary hover-success p-1" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="https://www.instagram.com/pkbmssupriadi/" target="_blank" class="text-secondary hover-success p-1" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.youtube.com/@PKBMSUPRIADI" target="_blank" class="text-secondary hover-success p-1" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                <a href="https://www.tiktok.com/@pkbmssupriadi" target="_blank" class="text-secondary hover-success p-1" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            </div>
            <span class="text-muted opacity-25">|</span>
            <a href="/admin" class="text-secondary text-decoration-none d-inline-flex align-items-center gap-1 hover-success small fw-medium" title="Buka Lingkungan Desktop Admin">
                <i class="fa-solid fa-display small"></i> SyntaxCore Desktop
            </a>
        </div>
    </div>
</div>

<!-- Main Navbar (Brand, Identity, & Header Area) -->
<nav class="navbar navbar-light bg-white py-2 py-lg-3 border-bottom main-navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- School Brand Logo -->
        <a class="navbar-brand d-flex align-items-center py-0 m-0" href="/">
            <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/logo1-pkbmsupriadi.sch_.id_.png" alt="PKBM S.Supriadi" style="height: 54px; max-height: 60px;">
        </a>

        <!-- PPDB Call-To-Action Button (Desktop) -->
        <div class="d-none d-lg-flex align-items-center">
            <a href="/page/ppdb" class="btn text-white rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-sm" style="background-color: #2db700; border: 2px solid #2db700; font-size: 14.5px; letter-spacing: 0.3px;">
                <i class="fa-solid fa-graduation-cap fs-5"></i>
                <span>PPDB 2026</span>
            </a>
        </div>

        <!-- Mobile Controls: Quick PPDB & Hamburger Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <a href="/page/ppdb" class="btn btn-sm text-white rounded-pill px-3 py-1 fw-bold shadow-sm" style="background-color: #2db700; font-size: 12.5px;">
                <i class="fa-solid fa-graduation-cap me-1"></i>PPDB
            </a>
            <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="collapse" data-bs-target="#pkbmNavbar" aria-controls="pkbmNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </div>
</nav>

<!-- Second Navbar: Specifically holds div#pkbmNavbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white second-navbar shadow-sm sticky-top py-0" style="border-bottom: 3px solid #2db700;">
    <div class="container">
        <!-- div#pkbmNavbar as requested -->
        <div class="collapse navbar-collapse" id="pkbmNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 py-2 py-lg-1 gap-lg-1">
                <?php foreach ($menus as $m): ?>
                    <?php
                        if (stripos($m['title'], 'PPDB') !== false) {
                            continue;
                        }
                        $hasChildren = !empty($m['children']);
                        $url = $m['computed_url'] ?? '/';
                        $isActive = ($activeMenu === $url) || ($url !== '/' && $url !== '#' && str_starts_with($activeMenu, $url));
                    ?>
                    <?php if ($hasChildren): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle py-2 px-2 <?= $isActive ? 'active fw-semibold' : '' ?>" href="<?= htmlspecialchars($url) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="<?= $isActive ? 'color: #2db700 !important;' : '' ?>">
                                <?= htmlspecialchars($m['title']) ?>
                            </a>
                            <ul class="dropdown-menu shadow border-0 rounded-3 mt-1 py-2">
                                <?php if ($url !== '#'): ?>
                                    <li>
                                        <a class="dropdown-item fw-semibold small py-2 text-muted" href="<?= htmlspecialchars($url) ?>">
                                            Semua <?= htmlspecialchars($m['title']) ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                <?php endif; ?>
                                <?php foreach ($m['children'] as $child): ?>
                                    <?php
                                        $childHasChildren = !empty($child['children']);
                                        $childUrl = $child['computed_url'] ?? '#';
                                    ?>
                                    <?php if ($childHasChildren): ?>
                                        <li class="dropdown-submenu px-2 py-1">
                                            <span class="dropdown-header text-uppercase fw-bold text-success px-1 pt-1 pb-1" style="font-size: 11px;">
                                                <?= htmlspecialchars($child['title']) ?>
                                            </span>
                                            <ul class="list-unstyled ps-2 mb-1">
                                                <?php foreach ($child['children'] as $subChild): ?>
                                                    <li>
                                                        <a class="dropdown-item py-1 small rounded-2" href="<?= htmlspecialchars($subChild['computed_url'] ?? '#') ?>">
                                                            <i class="fa-solid fa-angle-right me-1 text-muted" style="font-size: 9px;"></i>
                                                            <?= htmlspecialchars($subChild['title']) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item py-2 small" href="<?= htmlspecialchars($childUrl) ?>">
                                                <?= htmlspecialchars($child['title']) ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link py-2 px-2 <?= $isActive ? 'active fw-semibold' : '' ?>" href="<?= htmlspecialchars($url) ?>" style="<?= $isActive ? 'color: #2db700 !important;' : '' ?>">
                                <?= htmlspecialchars($m['title']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Right Controls: Warta & Berita Link -->
            <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                <a href="/berita" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none d-xl-inline-flex align-items-center gap-1" style="font-size: 12.5px;">
                    <i class="fa-solid fa-newspaper small"></i> Warta & Berita
                </a>
            </div>
        </div>
    </div>
</nav>
