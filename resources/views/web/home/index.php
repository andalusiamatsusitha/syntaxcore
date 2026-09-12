<?php
$page = $page ?? null;
$menus = $menus ?? [];
$recentNews = $recentNews ?? [];
$version = $version ?? '1.0.0';
$phpVersion = $phpVersion ?? PHP_VERSION;
$appName = $appName ?? 'PKBM S.Supriadi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="<?= htmlspecialchars($appName) ?>">
    <title><?= htmlspecialchars($appName === 'SyntaxCore' ? 'PKBM S.Supriadi' : $appName) ?> - Website Resmi Sekolah Kesetaraan Kota Malang</title>
    <meta name="description" content="Website Resmi PKBM S.Supriadi Malang. Sekolah nonformal kesetaraan Paket A, Paket B, dan Paket C dengan waktu belajar fleksibel dan keterampilan vokasi.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- FontAwesome Free 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333333;
        }

        .bg-school-green {
            background-color: #2db700 !important;
        }

        .text-school-green {
            color: #2db700 !important;
        }

        .btn-school-green {
            background-color: #2db700;
            border-color: #2db700;
            color: #ffffff;
        }

        .btn-school-green:hover {
            background-color: #259a00;
            border-color: #259a00;
            color: #ffffff;
        }

        .hero-slide-item {
            height: 480px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hero-slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.4) 100%);
            display: flex;
            align-items: center;
        }

        .home-stat-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease;
        }

        .home-stat-box:hover {
            transform: translateY(-3px);
        }

        .teacher-card {
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #edf2f7;
            transition: all 0.25s ease;
        }

        .teacher-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: #2db700;
        }

        .program-card {
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.25s ease;
        }

        .program-card:hover {
            border-color: #2db700;
            box-shadow: 0 8px 20px rgba(45, 183, 0, 0.12);
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Shared Public Navbar -->
    <?php include dirname(__DIR__) . '/layouts/navbar.php'; ?>

    <!-- 1. Hero Carousel Slider (4 Slides dari pkbmsupriadi.sch.id) -->
    <section class="position-relative overflow-hidden">
        <div id="homeHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
            </div>

            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <div class="hero-slide-item" style="background-image: url('https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_16-1200x845.jpg');">
                        <div class="hero-slide-overlay">
                            <div class="container text-white py-4">
                                <div class="col-12 col-lg-8">
                                    <span class="badge bg-school-green px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="letter-spacing: 0.8px;">Pendidikan Luar Kelas</span>
                                    <h1 class="display-6 fw-bold mb-3">Belajar dari Lingkungan Sekitar</h1>
                                    <p class="lead text-white-50 mb-4" style="font-size: 16px; line-height: 1.7;">
                                        Pembelajaran tidak selalu harus dilakukan di dalam kelas. Di PKBM S.Supriadi, peserta didik diajak belajar langsung dari lingkungan sekitar untuk mengamati, memahami, dan memecahkan persoalan nyata di lapangan.
                                    </p>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <a href="/page/profil-sekolah" class="btn btn-school-green px-4 py-2 rounded-pill fw-semibold shadow-sm">
                                            Profil Sekolah <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                        </a>
                                        <a href="/page/ppdb" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                                            Daftar PPDB 2026
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <div class="hero-slide-item" style="background-image: url('https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_3-e1772893795324-1200x845.jpg');">
                        <div class="hero-slide-overlay">
                            <div class="container text-white py-4">
                                <div class="col-12 col-lg-8">
                                    <span class="badge bg-school-green px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="letter-spacing: 0.8px;">Hak Semua Warga</span>
                                    <h1 class="display-6 fw-bold mb-3">Semua Berhak Mendapatkan Pendidikan</h1>
                                    <p class="lead text-white-50 mb-4" style="font-size: 16px; line-height: 1.7;">
                                        Pendidikan adalah hak setiap orang tanpa memandang usia, latar belakang, maupun kondisi ekonomi. Melalui jalur pendidikan nonformal kesetaraan, siapa pun tetap memiliki kesempatan untuk melanjutkan pendidikan dan meraih cita-cita.
                                    </p>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <a href="/page/visi-misi" class="btn btn-school-green px-4 py-2 rounded-pill fw-semibold shadow-sm">
                                            Visi &amp; Misi <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                        </a>
                                        <a href="https://wa.me/6285954447600" target="_blank" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                                            <i class="fa-brands fa-whatsapp me-1 text-success"></i> Konsultasi Belajar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <div class="hero-slide-item" style="background-image: url('https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_2-e1772888205840-1200x845.jpg');">
                        <div class="hero-slide-overlay">
                            <div class="container text-white py-4">
                                <div class="col-12 col-lg-8">
                                    <span class="badge bg-school-green px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="letter-spacing: 0.8px;">Metode Interaktif</span>
                                    <h1 class="display-6 fw-bold mb-3">Pembelajaran Luar Kelas Solusi Belajar Aktif</h1>
                                    <p class="lead text-white-50 mb-4" style="font-size: 16px; line-height: 1.7;">
                                        Suasana belajar yang santai namun tetap edukatif mampu meningkatkan motivasi belajar dan membuat peserta didik lebih antusias dalam mengikuti kegiatan pembelajaran.
                                    </p>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <a href="/page/ekstrakurikuler" class="btn btn-school-green px-4 py-2 rounded-pill fw-semibold shadow-sm">
                                            Program Intrakurikuler <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 4 -->
                <div class="carousel-item">
                    <div class="hero-slide-item" style="background-image: url('https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_750kb-e1772887763195-1200x845.jpg');">
                        <div class="hero-slide-overlay">
                            <div class="container text-white py-4">
                                <div class="col-12 col-lg-8">
                                    <span class="badge bg-school-green px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="letter-spacing: 0.8px;">Fleksibel &amp; Berkualitas</span>
                                    <h1 class="display-6 fw-bold mb-3">Fleksibelnya Belajar di PKBM S.Supriadi</h1>
                                    <p class="lead text-white-50 mb-4" style="font-size: 16px; line-height: 1.7;">
                                        Peserta didik dapat menyesuaikan waktu belajar dengan aktivitas harian, sehingga tetap bisa bekerja atau menjalankan wirausaha tanpa harus meninggalkan pendidikan kesetaraan.
                                    </p>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <a href="/page/identitas-sekolah" class="btn btn-school-green px-4 py-2 rounded-pill fw-semibold shadow-sm">
                                            Legalitas &amp; NPSN <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                        </a>
                                        <a href="/page/ppdb" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                                            Daftar Siswa Baru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Sebelumnya</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Selanjutnya</span>
            </button>
        </div>
    </section>

    <!-- 2. Sambutan Kepala Sekolah & Statistik Sekolah -->
    <section class="py-5 bg-white border-bottom">
        <div class="container">
            <div class="row align-items-center g-4">
                <!-- Sambutan Kepala Sekolah -->
                <div class="col-12 col-lg-7">
                    <div class="p-4 p-md-5 rounded-4 bg-light border shadow-sm h-100">
                        <div class="row align-items-center g-4">
                            <div class="col-12 col-sm-4 text-center">
                                <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_batik_700kb-e1772858295746.jpg" alt="Muh Fajar Mujahid, S.Pd" class="img-fluid rounded-3 shadow border" style="max-height: 220px; object-fit: cover;">
                            </div>
                            <div class="col-12 col-sm-8">
                                <span class="badge bg-success mb-2 px-3 py-1 rounded-pill">Kepala Sekolah</span>
                                <h3 class="fw-bold mb-1 text-dark">Muh Fajar Mujahid, S.Pd</h3>
                                <p class="text-muted small mb-3">Kepala PKBM S. Supriadi Kota Malang</p>
                                <p class="small text-secondary mb-3" style="line-height: 1.7;">
                                    "Puji syukur kita panjatkan ke hadirat Allah SWT atas segala rahmat dan karunia-Nya sehingga website PKBM ini dapat hadir sebagai sarana informasi dan komunikasi bagi masyarakat..."
                                </p>
                                <a href="/page/sambutan-kepala-sekolah" class="btn btn-sm btn-school-green rounded-pill px-3 fw-semibold">
                                    Baca Sambutan Lengkap <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistik Data Sekolah -->
                <div class="col-12 col-lg-5">
                    <div class="text-center mb-3">
                        <h4 class="fw-bold text-dark mb-1">Statistik Data Sekolah</h4>
                        <p class="text-muted small mb-0">Cakupan penyelenggaraan pendidikan kesetaraan kami</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="home-stat-box">
                                <i class="fa-solid fa-chalkboard-user text-school-green fs-2 mb-2"></i>
                                <div class="display-6 fw-bold text-dark">12</div>
                                <div class="small text-muted fw-medium">Guru &amp; Staf</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="home-stat-box">
                                <i class="fa-solid fa-user-graduate text-school-green fs-2 mb-2"></i>
                                <div class="display-6 fw-bold text-dark">332</div>
                                <div class="small text-muted fw-medium">Warga Belajar</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="home-stat-box">
                                <i class="fa-solid fa-layer-group text-school-green fs-2 mb-2"></i>
                                <div class="display-6 fw-bold text-dark">16</div>
                                <div class="small text-muted fw-medium">Rombel</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-white rounded-3 border text-center">
                        <span class="small text-muted">
                            <i class="fa-solid fa-circle-check text-success me-1"></i> Data terdaftar dan terverifikasi secara berkala di Dapodikdasmen
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Berita, Artikel & Informasi (Warta & Berita) -->
    <section class="py-5" style="background-color: #e8f5ff;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                <div>
                    <span class="badge bg-school-green px-3 py-1 rounded-pill mb-2">Informasi Aktual</span>
                    <h2 class="fw-bold text-dark mb-1">Warta &amp; Berita Terkini</h2>
                    <p class="text-muted small mb-0">Liputan kegiatan, pelaksanaan ujian, serta artikel edukatif PKBM S.Supriadi</p>
                </div>
                <a href="/berita" class="btn btn-sm btn-outline-success rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1">
                    <span>Semua Warta &amp; Berita</span> <i class="fa-solid fa-arrow-right small"></i>
                </a>
            </div>

            <?php if (!empty($recentNews)): ?>
                <div class="row g-4">
                    <?php foreach ($recentNews as $n): ?>
                        <?php
                            $nCat = $n->category();
                            $nAuthor = $n->author();
                        ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                                <div class="position-relative bg-light" style="height: 190px;">
                                    <?php if (!empty($n->featured_image)): ?>
                                        <img src="<?= htmlspecialchars($n->featured_image) ?>" alt="<?= htmlspecialchars($n->title) ?>" class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-muted">
                                            <i class="fa-solid fa-newspaper fs-1 text-secondary opacity-25"></i>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($nCat): ?>
                                        <div class="position-absolute top-0 start-0 m-3">
                                            <span class="badge shadow-sm" style="background-color: <?= htmlspecialchars($nCat->color ?: '#2db700') ?>;">
                                                <?= htmlspecialchars($nCat->name) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                    <div class="small text-muted mb-2 font-monospace" style="font-size: 11.5px;">
                                        <i class="fa-regular fa-calendar me-1 text-success"></i><?= date('d M Y', strtotime($n->published_at ?: $n->created_at)) ?>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2" style="font-size: 16px; line-height: 1.4;">
                                        <a href="/berita/<?= htmlspecialchars($n->slug) ?>" class="text-dark text-decoration-none hover-success">
                                            <?= htmlspecialchars($n->title) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                                        <?= htmlspecialchars(mb_substr(strip_tags($n->summary ?: $n->content), 0, 110)) ?>...
                                    </p>
                                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="small text-muted" style="font-size: 12px;">
                                            <i class="fa-regular fa-eye me-1"></i><?= $n->views_count ?> dilihat
                                        </span>
                                        <a href="/berita/<?= htmlspecialchars($n->slug) ?>" class="text-success text-decoration-none small fw-semibold">
                                            Baca Detail <i class="fa-solid fa-arrow-right small"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-light border p-4 text-center text-muted">
                    Belum ada warta berita yang dipublikasikan.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 4. Staf Pengajar (Guru & Tutor) -->
    <section class="py-5 bg-white border-bottom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                <div>
                    <span class="badge bg-school-green px-3 py-1 rounded-pill mb-2">Tenaga Pendidik</span>
                    <h2 class="fw-bold text-dark mb-1">Guru &amp; Tutor Pengajar</h2>
                    <p class="text-muted small mb-0">Pengajar berkompeten dan ramah yang membimbing warga belajar</p>
                </div>
                <a href="/page/staf-pengajar" class="btn btn-sm btn-outline-success rounded-pill px-3 py-2 fw-semibold">
                    Semua Guru <i class="fa-solid fa-arrow-right ms-1 small"></i>
                </a>
            </div>

            <div class="row g-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_formal_hijab_700kb-386x500.jpg" alt="Adinda Sukma" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Adinda Sukma N., S.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">Bahasa Jawa, PKn</small>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/08/WhatsApp-Image-2026-08-26-at-12.46.21-386x500.jpeg" alt="Alfindha Rosa" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Alfindha Rosa S., S.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">IPA, Matematika</small>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_flanel_700kb-386x500.jpg" alt="Dimas Arya" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Dimas Arya A. N., S.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">Teknologi Informasi</small>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_batik_1200x245-1-386x500.jpg" alt="Ilham Gus" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Ilham Gus A., M.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">Bahasa Inggris</small>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/pasfoto_700kb-386x500.jpg" alt="Retno Wahyu" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Retno Wahyu K., S.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">Bahasa Indonesia, IPS</small>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="teacher-card p-3 text-center h-100">
                        <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_work_jacket_700kb-386x500.jpg" alt="Zulius Nilam" class="img-fluid rounded-3 mb-2 mx-auto" style="height: 140px; object-fit: cover;">
                        <h6 class="fw-bold text-dark mb-1 small">Zulius Nilam C., S.Pd</h6>
                        <small class="text-muted" style="font-size: 11px;">Bahasa Indonesia</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Intrakurikuler & Program Keterampilan -->
    <section class="py-5 bg-light border-bottom">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-school-green px-3 py-1 rounded-pill mb-2">Keterampilan Vokasi</span>
                <h2 class="fw-bold text-dark mb-2">Program Intrakurikuler</h2>
                <p class="text-muted small mx-auto" style="max-width: 580px;">
                    Membekali setiap warga belajar dengan keterampilan praktis, kemandirian karakter, dan daya saing untuk masa depan.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="program-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-laptop-code fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Computer Class</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.65;">
                            Pembelajaran komputer praktis: aplikasi perkantoran, pengetikan naskah, desain grafis dasar, serta literasi digital untuk dunia kerja.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="program-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-utensils fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Cooking Class</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.65;">
                            Kelas tata boga dan kreasi kuliner untuk melatih keterampilan memasak dan menumbuhkan jiwa wirausaha di bidang kuliner.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="program-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-language fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">English Club</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.65;">
                            Klub percakapan bahasa Inggris aplikatif untuk meningkatkan keberanian berkomunikasi aktif dan memperluas wawasan global.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-6">
                    <div class="program-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-bus-simple fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Outing Class</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.65;">
                            Kegiatan belajar di luar kelas mengunjungi museum, instansi kebudayaan, dan cagar alam untuk memberikan pengalaman belajar nyata.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-6">
                    <div class="program-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 bg-danger-subtle text-danger p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-campground fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">SuperCamp</h5>
                        </div>
                        <p class="text-muted small mb-0" style="line-height: 1.65;">
                            Perkemahan edukatif di alam terbuka yang melatih kepemimpinan, kekompakan kelompok, kemandirian, dan kepedulian terhadap lingkungan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Testimoni Alumni -->
    <section class="py-5 bg-white border-bottom">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-school-green px-3 py-1 rounded-pill mb-2">Suara Alumni</span>
                <h2 class="fw-bold text-dark mb-2">Testimoni Warga Belajar</h2>
                <p class="text-muted small">Kesan dan pengalaman nyata belajar di PKBM S.Supriadi</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-12 col-md-6">
                    <div class="card h-100 p-4 border rounded-4 shadow-sm bg-light">
                        <i class="fa-solid fa-quote-left fs-2 text-school-green mb-3"></i>
                        <p class="text-secondary small mb-4" style="line-height: 1.75;">
                            "Belajar di PKBM S.Supriadi sangat membantu saya yang harus bekerja sambil melanjutkan pendidikan. Dengan sistem pembelajaran yang fleksibel, saya tetap bisa sekolah tanpa harus meninggalkan pekerjaan. Para tutor juga sangat membantu dan memberikan kemudahan dalam proses belajar. Berkat PKBM S.Supriadi, saya bisa melanjutkan pendidikan dan mempersiapkan masa depan yang lebih baik."
                        </p>
                        <div class="d-flex align-items-center gap-3 mt-auto pt-3 border-top">
                            <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/foto_700kb_13-e1773062242131-100x100.jpg" alt="Ismail" class="rounded-circle shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Ismail</h6>
                                <small class="text-muted">Alumni Paket C 2025</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card h-100 p-4 border rounded-4 shadow-sm bg-light">
                        <i class="fa-solid fa-quote-left fs-2 text-school-green mb-3"></i>
                        <p class="text-secondary small mb-4" style="line-height: 1.75;">
                            "Belajar di PKBM S.Supriadi memberikan banyak perubahan bagi saya. Awalnya saya adalah orang yang pemalu dan kurang percaya diri. Namun selama belajar di sini, saya mendapatkan banyak pengalaman, teman, dan dukungan dari para tutor. Sekarang saya menjadi lebih berani, lebih percaya diri, dan siap melangkah untuk masa depan yang lebih baik."
                        </p>
                        <div class="d-flex align-items-center gap-3 mt-auto pt-3 border-top">
                            <img src="https://pkbmsupriadi.sch.id/wp-content/uploads/2026/03/IMG_1708-100x100.jpg" alt="Muhammad Hamza Zidan" class="rounded-circle shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Muhammad Hamza Zidan</h6>
                                <small class="text-muted">Alumni Paket B 2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. Call To Action (PPDB 2026) -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, #1e3a1e 0%, #15803d 100%);">
        <div class="container text-center py-3">
            <h2 class="display-6 fw-bold mb-3">Siap Meraih Ijazah &amp; Masa Depan Lebih Baik?</h2>
            <p class="lead text-white-50 mx-auto mb-4" style="max-width: 620px; font-size: 16px;">
                Daftarkan diri Anda atau putra-putri Anda di PKBM S.Supriadi. Waktu belajar fleksibel, pengajar sabar, dan terakreditasi resmi.
            </p>
            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                <a href="/page/ppdb" class="btn btn-warning px-4 py-2 rounded-pill fw-bold text-dark shadow">
                    <i class="fa-solid fa-graduation-cap me-1"></i> Informasi PPDB 2026
                </a>
                <a href="https://wa.me/6285954447600?text=Halo%20Admin%20PKBM%20S.Supriadi,%20saya%20ingin%20konsultasi%20pendaftaran" target="_blank" class="btn btn-light px-4 py-2 rounded-pill fw-bold text-success shadow">
                    <i class="fa-brands fa-whatsapp me-1"></i> Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </section>

    <!-- Shared Public Footer -->
    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

</body>
</html>
