<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="small">Total Kamar</div>
                <div class="fs-3 fw-bold"><?= esc($totalRooms) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="small">Kamar Tersedia</div>
                <div class="fs-3 fw-bold"><?= esc($roomStats['available']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm bg-warning text-dark">
            <div class="card-body">
                <div class="small">Kamar Terisi</div>
                <div class="fs-3 fw-bold"><?= esc($roomStats['occupied']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm bg-info text-white">
            <div class="card-body">
                <div class="small">Reservasi Hari Ini</div>
                <div class="fs-3 fw-bold"><?= esc($reservationsToday) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Check In Hari Ini</div>
                <div class="fs-4 fw-bold"><?= esc($checkInsToday) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Check Out Hari Ini</div>
                <div class="fs-4 fw-bold"><?= esc($checkOutsToday) ?></div>
            </div>
        </div>
    </div>

    <?php if ($canSeeRevenue): ?>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Pendapatan Hari Ini</div>
                    <div class="fs-5 fw-bold">Rp <?= number_format($revenueToday, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Pendapatan Bulan Ini</div>
                    <div class="fs-5 fw-bold">Rp <?= number_format($revenueThisMonth, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-<?= $canSeeRevenue ? '6' : '12' ?>">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Grafik Reservasi (7 Hari Terakhir)</h6>
                <canvas id="reservationChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <?php if ($canSeeRevenue): ?>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Grafik Pendapatan (6 Bulan Terakhir)</h6>
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Booking Terbaru</h6>
        <?php if (empty($recentBookings)): ?>
            <p class="text-muted mb-0">Belum ada reservasi.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No. Booking</th>
                            <th>Customer</th>
                            <th>Kamar</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td><?= esc($b['booking_number']) ?></td>
                                <td><?= esc($b['customer_name']) ?></td>
                                <td><?= esc($b['room_number']) ?></td>
                                <td><?= esc(date('d M Y', strtotime($b['check_in_date']))) ?></td>
                                <td><?= esc(date('d M Y', strtotime($b['check_out_date']))) ?></td>
                                <td><span class="badge bg-secondary"><?= esc($b['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const reservationData = <?= json_encode($reservationChart) ?>;
new Chart(document.getElementById('reservationChart'), {
    type: 'line',
    data: {
        labels: reservationData.map(d => d.date),
        datasets: [{
            label: 'Jumlah Reservasi',
            data: reservationData.map(d => d.total),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,.1)',
            tension: .3,
            fill: true,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

<?php if ($canSeeRevenue): ?>
const revenueData = <?= json_encode($revenueChart) ?>;
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: revenueData.map(d => d.month),
        datasets: [{
            label: 'Pendapatan',
            data: revenueData.map(d => d.total),
            backgroundColor: '#198754',
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>