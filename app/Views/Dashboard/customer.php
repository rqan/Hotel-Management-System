<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Selamat datang, <?= esc($name) ?> 👋</h5>
        <p class="text-muted mb-0">Berikut ringkasan booking Anda.</p>
    </div>
</div>

<?php if (!$hasCustomerProfile): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Anda belum memiliki riwayat booking. Silakan lakukan reservasi pertama Anda.
    </div>
<?php else: ?>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="small">Reservasi Aktif</div>
                    <div class="fs-3 fw-bold"><?= esc($activeCount) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title">Riwayat Booking</h6>
            <?php if (empty($reservations)): ?>
                <p class="text-muted mb-0">Belum ada riwayat booking.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No. Booking</th>
                                <th>Tipe Kamar</th>
                                <th>No. Kamar</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Malam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td><?= esc($r['booking_number']) ?></td>
                                    <td><?= esc($r['room_type_name']) ?></td>
                                    <td><?= esc($r['room_number']) ?></td>
                                    <td><?= esc(date('d M Y', strtotime($r['check_in_date']))) ?></td>
                                    <td><?= esc(date('d M Y', strtotime($r['check_out_date']))) ?></td>
                                    <td><?= esc($r['nights']) ?></td>
                                    <td><span class="badge bg-secondary"><?= esc($r['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?= $this->endSection() ?>