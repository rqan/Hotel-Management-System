<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Dashboard') ?> - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .sidebar {
            min-height: 100vh;
            width: 240px;
            background: #1e2a38;
        }
        .sidebar .nav-link {
            color: #b8c2cc;
            padding: .65rem 1rem;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            color: #fff;
            background: #2b3a4d;
        }
        .sidebar .brand {
            color: #fff;
            padding: 1rem;
            border-bottom: 1px solid #2b3a4d;
        }
        .main-content { flex: 1; }
        .stat-card { border: none; border-radius: .75rem; }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="d-flex">
        <!-- SIDEBAR -->
        <nav class="sidebar d-flex flex-column">
            <div class="brand">
                <i class="bi bi-building"></i> <strong>HMS</strong>
            </div>
            <div class="nav flex-column mt-2">
                <a href="<?= base_url('dashboard') ?>" class="nav-link">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <?php if (has_role(['super_admin', 'admin'])): ?>
                    <div class="text-uppercase small text-secondary px-3 mt-3 mb-1">Master Data</div>
                    <a href="<?= base_url('master/room-types') ?>" class="nav-link"><i class="bi bi-door-closed"></i> Tipe Kamar</a>
                    <a href="<?= base_url('master/rooms') ?>" class="nav-link"><i class="bi bi-house"></i> Kamar</a>
                    <a href="<?= base_url('master/facilities') ?>" class="nav-link"><i class="bi bi-list-check"></i> Fasilitas</a>
                    <a href="<?= base_url('master/customers') ?>" class="nav-link"><i class="bi bi-people"></i> Customer</a>
                <?php endif; ?>

                <?php if (is_staff()): ?>
                    <div class="text-uppercase small text-secondary px-3 mt-3 mb-1">Operasional</div>
                    <a href="<?= base_url('reservation') ?>" class="nav-link"><i class="bi bi-calendar-check"></i> Reservasi</a>
                    <a href="<?= base_url('checkin') ?>" class="nav-link"><i class="bi bi-box-arrow-in-right"></i> Check In</a>
                    <a href="<?= base_url('checkout') ?>" class="nav-link"><i class="bi bi-box-arrow-right"></i> Check Out</a>
                    <a href="<?= base_url('payment') ?>" class="nav-link"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                <?php endif; ?>

                <?php if (has_role(['super_admin', 'admin', 'manager'])): ?>
                    <div class="text-uppercase small text-secondary px-3 mt-3 mb-1">Laporan</div>
                    <a href="<?= base_url('reports') ?>" class="nav-link"><i class="bi bi-graph-up"></i> Laporan</a>
                <?php endif; ?>

                <?php if (has_role(['customer'])): ?>
                    <div class="text-uppercase small text-secondary px-3 mt-3 mb-1">Booking</div>
                    <a href="<?= base_url('my-reservations') ?>" class="nav-link"><i class="bi bi-journal-text"></i> Riwayat Booking</a>
                <?php endif; ?>

                <?php if (has_role(['super_admin'])): ?>
                    <div class="text-uppercase small text-secondary px-3 mt-3 mb-1">Sistem</div>
                    <a href="<?= base_url('users') ?>" class="nav-link"><i class="bi bi-person-badge"></i> Manajemen User</a>
                    <a href="<?= base_url('settings') ?>" class="nav-link"><i class="bi bi-gear"></i> Pengaturan</a>
                    <a href="<?= base_url('activity-logs') ?>" class="nav-link"><i class="bi bi-clock-history"></i> Activity Log</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- MAIN -->
        <div class="main-content">
            <!-- NAVBAR -->
            <nav class="navbar navbar-light bg-white shadow-sm px-3">
                <span class="fw-semibold"><?= esc($title ?? 'Dashboard') ?></span>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 me-2"></i>
                        <?= esc(session()->get('userName')) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('change-password') ?>"><i class="bi bi-key"></i> Ubah Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <!-- FLASH MESSAGES -->
            <div class="p-3 pb-0">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CONTENT -->
            <div class="p-3">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>