<?php
/**
 * Shared Public Website Footer
 */
?>
<footer class="bg-dark text-white-50 py-5 mt-auto border-top border-secondary border-opacity-25">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-5">
                <div class="d-flex align-items-center gap-2 mb-2 text-white fw-bold fs-5">
                    <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded px-2 py-1 text-white shadow-sm" style="font-size: 13px;">
                        <i class="fa-solid fa-cube me-1"></i> SC
                    </span>
                    <span>Syntax<span class="text-primary">Core</span> Framework</span>
                </div>
                <p class="small text-muted mb-3" style="max-width: 420px;">
                    Framework PHP MVC murni yang cepat, elegan, dan fleksibel. Mengintegrasikan kemudahan Content Management System modern dengan pengalaman Desktop Window Environment multitasking.
                </p>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-secondary-subtle text-white border border-secondary border-opacity-25 small font-monospace">PHP <?= PHP_VERSION ?></span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-monospace">MVC Pure</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <h6 class="text-white fw-semibold mb-3 small text-uppercase" style="letter-spacing: 0.5px;">Navigasi Cepat</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="/" class="text-white-50 text-decoration-none hover-white">Beranda Utama</a></li>
                    <li><a href="/berita" class="text-white-50 text-decoration-none hover-white">Warta & Berita</a></li>
                    <li><a href="/tentang-kami" class="text-white-50 text-decoration-none hover-white">Tentang Kami</a></li>
                    <li><a href="/admin" class="text-white-50 text-decoration-none hover-white">Portal Admin Desktop</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-4">
                <h6 class="text-white fw-semibold mb-3 small text-uppercase" style="letter-spacing: 0.5px;">Manajemen Konten</h6>
                <p class="small text-muted mb-2">
                    Seluruh konten artikel, halaman custom, dan konfigurasi komentar dikelola secara real-time dari panel administratif.
                </p>
                <div class="pt-2">
                    <a href="/admin/login" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-lock small"></i> Akses Panel Admin
                    </a>
                </div>
            </div>
        </div>

        <hr class="border-secondary border-opacity-25 my-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
            <div>
                &copy; <?= date('Y') ?> <strong>SyntaxCore</strong>. Seluruh hak cipta dilindungi undang-undang.
            </div>
            <div>
                Ditenagai oleh arsitektur modular murni tanpa bloatware.
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
<!-- App JS -->
<script src="/assets/js/app.js" defer></script>
