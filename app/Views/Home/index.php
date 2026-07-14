<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($settings['hotel_name'] ?? 'Hotel Management System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .hero {
            background: linear-gradient(rgba(30,42,56,.75), rgba(30,42,56,.75)),
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600') center/cover no-repeat;
            color: #fff;
            padding: 120px 0 100px;
        }
        .navbar-brand { font-weight: bold; }
        .room-card { border: none; border-radius: 12px; overflow: hidden; transition: transform .2s; }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        .room-photo { height: 200px; background: #e9ecef; display: flex; align-items: center; justify-content: center; color: #adb5bd; font-size: 40px; }
        .facility-badge { background: #f4f6f9; border-radius: 8px; padding: 15px; text-align: center; }
        .section-title { font-weight: bold; margin-bottom: 40px; }
        footer { background: #1e2a38; color: #b8c2cc; padding: 40px 0; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('/') ?>">
                <i class="bi bi-building"></i> <?= esc($settings['hotel_name'] ?? 'Hotel Management System') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navMenu">
                <ul class="navbar-nav align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#rooms">Kamar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#facilities">Fasilitas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="<?= base_url('login') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm" href="<?= base_url('register') ?>">Daftar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Selamat Datang di <?= esc($settings['hotel_name'] ?? 'Hotel Kami') ?></h1>
            <p class="lead mb-4">Kenyamanan menginap terbaik dengan pelayanan profesional, harga terjangkau, dan fasilitas lengkap.</p>
            <a href="#rooms" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-search"></i> Lihat Pilihan Kamar
            </a>
        </div>
    </section>

    <!-- ROOM TYPES -->
    <section id="rooms" class="py-5">
        <div class="container">
            <h2 class="section-title text-center">Pilihan Kamar</h2>

            <?php if (empty($roomTypes)): ?>
                <p class="text-center text-muted">Belum ada tipe kamar yang tersedia saat ini.</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($roomTypes as $rt): ?>
                        <div class="col-md-4">
                            <div class="card room-card shadow-sm h-100">
                                <?php if (!empty($rt['photo'])): ?>
                                    <img src="<?= base_url('uploads/roomtypes/' . $rt['photo']) ?>" class="room-photo" style="object-fit:cover;" alt="<?= esc($rt['name']) ?>">
                                <?php else: ?>
                                    <div class="room-photo"><i class="bi bi-door-closed"></i></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= esc($rt['name']) ?></h5>
                                    <p class="card-text text-muted small"><?= esc($rt['description'] ?? 'Kamar nyaman untuk menginap Anda.') ?></p>
                                    <p class="mb-1"><i class="bi bi-people"></i> Kapasitas <?= esc($rt['capacity']) ?> orang</p>
                                    <h5 class="text-primary mt-2">Rp <?= number_format($rt['price'], 0, ',', '.') ?> <small class="text-muted fs-6">/ malam</small></h5>
                                </div>
                                <div class="card-footer bg-white border-0 pb-3">
                                    <a href="<?= base_url('register') ?>" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-calendar-check"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FACILITIES -->
    <section id="facilities" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center">Fasilitas Hotel</h2>

            <?php if (empty($facilities)): ?>
                <p class="text-center text-muted">Informasi fasilitas belum tersedia.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($facilities as $f): ?>
                        <div class="col-6 col-md-3">
                            <div class="facility-badge">
                                <i class="bi <?= esc($f['icon'] ?? 'bi-check-circle') ?> fs-3 text-primary"></i>
                                <div class="mt-2 small fw-semibold"><?= esc($f['name']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-5 text-center">
        <div class="container">
            <h3 class="fw-bold mb-3">Siap untuk menginap bersama kami?</h3>
            <p class="text-muted mb-4">Daftar sekarang dan nikmati kemudahan reservasi online.</p>
            <a href="<?= base_url('register') ?>" class="btn btn-primary btn-lg px-4 me-2">Daftar Akun</a>
            <a href="<?= base_url('login') ?>" class="btn btn-outline-secondary btn-lg px-4">Sudah Punya Akun</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="text-white"><i class="bi bi-building"></i> <?= esc($settings['hotel_name'] ?? 'Hotel Management System') ?></h5>
                    <p class="small mb-1"><?= esc($settings['address'] ?? '') ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if (!empty($settings['phone'])): ?>
                        <p class="small mb-1"><i class="bi bi-telephone"></i> <?= esc($settings['phone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($settings['email'])): ?>
                        <p class="small mb-1"><i class="bi bi-envelope"></i> <?= esc($settings['email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="border-secondary">
            <p class="text-center small mb-0">&copy; <?= date('Y') ?> <?= esc($settings['hotel_name'] ?? 'Hotel Management System') ?>. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>