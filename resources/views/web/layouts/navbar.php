<?php
/**
 * Shared Public Website Navbar
 *
 * @var array $menus Hierarchical tree from PublicMenu::tree(true)
 * @var string $activeMenu Current active menu route (e.g. '/', '/berita', '/tentang-kami')
 */
$menus = $menus ?? \App\Models\PublicMenu::tree(true);
$activeMenu = $activeMenu ?? '/';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white" href="/">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded px-2 py-1 text-white shadow-sm" style="font-size: 14px; letter-spacing: 0.5px;">
                <i class="fa-solid fa-cube me-1"></i> SC
            </span>
            <span class="fs-5 tracking-wide">Syntax<span class="text-primary">Core</span></span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#syntaxCoreNavbar" aria-controls="syntaxCoreNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="syntaxCoreNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ps-lg-3">
                <?php foreach ($menus as $m): ?>
                    <?php
                        $hasChildren = !empty($m['children']);
                        $url = $m['computed_url'] ?? '/';
                        $isActive = ($activeMenu === $url) || ($url !== '/' && str_starts_with($activeMenu, $url));
                    ?>
                    <?php if ($hasChildren): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= $isActive ? 'active fw-semibold text-white' : '' ?>" href="<?= htmlspecialchars($url) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= htmlspecialchars($m['title']) ?>
                            </a>
                            <ul class="dropdown-menu shadow border-0 rounded-3 mt-1">
                                <li>
                                    <a class="dropdown-item fw-semibold text-muted small" href="<?= htmlspecialchars($url) ?>">
                                        Semua <?= htmlspecialchars($m['title']) ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <?php foreach ($m['children'] as $child): ?>
                                    <li>
                                        <a class="dropdown-item py-1 text-dark" href="<?= htmlspecialchars($child['computed_url'] ?? '#') ?>">
                                            <?= htmlspecialchars($child['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActive ? 'active fw-semibold text-white' : '' ?>" href="<?= htmlspecialchars($url) ?>">
                                <?= htmlspecialchars($m['title']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Right Controls: Admin Quick Access -->
            <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0">
                <a href="/berita" class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-magnifying-glass small"></i> Cari Berita
                </a>
                <a href="/admin" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-display small"></i> Admin Desktop
                </a>
            </div>
        </div>
    </div>
</nav>
