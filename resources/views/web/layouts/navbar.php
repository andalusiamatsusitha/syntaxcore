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
                <span class="badge bg-success-subtle text-success border border-success-subtle">NPSN P9962749 &bull; Terakreditasi</span>
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

<!-- Main Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-2" style="border-bottom: 3px solid #2db700;">
    <div class="container">
        <!-- School Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2 py-0" href="/">
            <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/logo1-pkbmsupriadi.sch_.id_.png" alt="PKBM S.Supriadi" style="height: 48px;" onerror="this.style.display='none'">
            <div class="d-flex flex-column">
                <span class="fw-bold text-dark fs-5 tracking-tight" style="line-height: 1.15;">PKBM <span style="color: #2db700;">S.Supriadi</span></span>
                <small class="text-muted font-monospace text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Pendidikan Kesetaraan Malang</small>
            </div>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#pkbmNavbar" aria-controls="pkbmNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu List -->
        <div class="collapse navbar-collapse" id="pkbmNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ps-lg-3 gap-lg-1">
                <?php foreach ($menus as $m): ?>
                    <?php
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
                                    <li>
                                        <a class="dropdown-item py-2 small d-flex justify-content-between align-items-center" href="<?= htmlspecialchars($childUrl) ?>">
                                            <span><?= htmlspecialchars($child['title']) ?></span>
                                            <?php if ($childHasChildren): ?>
                                                <i class="fa-solid fa-chevron-right text-muted" style="font-size: 10px;"></i>
                                            <?php endif; ?>
                                        </a>
                                    </li>
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

            <!-- Right Controls: Search & PPDB 2026 CTA -->
            <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0">
                <a href="/berita" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none d-xl-inline-flex align-items-center gap-1" style="font-size: 12px;">
                    <i class="fa-solid fa-magnifying-glass small"></i> Warta & Berita
                </a>
                <a href="/page/ppdb" class="btn btn-sm text-white rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" style="background-color: #2db700; border-color: #2db700; font-size: 13px;">
                    <i class="fa-solid fa-graduation-cap"></i> PPDB 2026
                </a>
            </div>
        </div>
    </div>
</nav>
