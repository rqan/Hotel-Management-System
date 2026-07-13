<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="startDate" value="<?= esc($startDate) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="endDate" value="<?= esc($endDate) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="reportTabs">
    <li class="nav-item"><a class="nav-link active" data-type="revenue" href="#">Pendapatan</a></li>
    <li class="nav-item"><a class="nav-link" data-type="reservation" href="#">Reservasi</a></li>
    <li class="nav-item"><a class="nav-link" data-type="checkin" href="#">Check In</a></li>
    <li class="nav-item"><a class="nav-link" data-type="checkout" href="#">Check Out</a></li>
    <li class="nav-item"><a class="nav-link" data-type="room" href="#">Kamar</a></li>
    <li class="nav-item"><a class="nav-link" data-type="customer" href="#">Customer</a></li>
</ul>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0" id="reportTitle">Laporan Pendapatan</h6>
            <div>
                <a href="#" id="btnExportExcel" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="#" id="btnExportPdf" class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
            </div>
        </div>

        <div id="summaryArea" class="mb-3"></div>

        <div class="table-responsive">
            <table id="reportTable" class="table table-sm table-hover w-100">
                <thead id="reportTableHead"></thead>
                <tbody id="reportTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const baseUrl = '<?= base_url() ?>';
let currentType = 'revenue';

const reportMeta = {
    revenue:     { title: 'Laporan Pendapatan', headers: ['No. Pembayaran', 'No. Invoice', 'No. Booking', 'Customer', 'Metode', 'Jumlah', 'Tanggal Bayar'] },
    reservation: { title: 'Laporan Reservasi', headers: ['No. Booking', 'Customer', 'Kamar', 'Tipe', 'Check In', 'Check Out', 'Malam', 'Status'] },
    checkin:     { title: 'Laporan Check In', headers: ['No. Booking', 'Customer', 'Kamar', 'Waktu Check In', 'Diproses Oleh'] },
    checkout:    { title: 'Laporan Check Out', headers: ['No. Booking', 'Customer', 'Kamar', 'Waktu Check Out', 'Total Biaya', 'Diproses Oleh'] },
    room:        { title: 'Laporan Kamar (Okupansi)', headers: ['No. Kamar', 'Tipe Kamar', 'Total Reservasi', 'Total Malam Terisi'] },
    customer:    { title: 'Laporan Customer', headers: ['Nama', 'No HP', 'Email', 'Total Reservasi', 'Total Pengeluaran'] },
};

function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
function getDates() {
    return { start: document.getElementById('startDate').value, end: document.getElementById('endDate').value };
}

function renderRow(type, row) {
    switch (type) {
        case 'revenue':
            return `<tr>
                <td>${row.payment_number}</td><td>${row.invoice_number}</td><td>${row.booking_number}</td>
                <td>${row.customer_name}</td><td>${row.method.toUpperCase()}</td>
                <td>${formatRupiah(row.amount)}</td><td>${new Date(row.paid_at).toLocaleString('id-ID')}</td>
            </tr>`;
        case 'reservation':
            return `<tr>
                <td>${row.booking_number}</td><td>${row.customer_name}</td><td>${row.room_number}</td>
                <td>${row.room_type_name}</td><td>${new Date(row.check_in_date).toLocaleDateString('id-ID')}</td>
                <td>${new Date(row.check_out_date).toLocaleDateString('id-ID')}</td><td>${row.nights}</td>
                <td><span class="badge bg-secondary">${row.status}</span></td>
            </tr>`;
        case 'checkin':
            return `<tr>
                <td>${row.booking_number}</td><td>${row.customer_name}</td><td>${row.room_number}</td>
                <td>${new Date(row.checked_in_at).toLocaleString('id-ID')}</td><td>${row.processed_by}</td>
            </tr>`;
        case 'checkout':
            return `<tr>
                <td>${row.booking_number}</td><td>${row.customer_name}</td><td>${row.room_number}</td>
                <td>${new Date(row.checked_out_at).toLocaleString('id-ID')}</td>
                <td>${formatRupiah(row.total_amount)}</td><td>${row.processed_by}</td>
            </tr>`;
        case 'room':
            return `<tr>
                <td>${row.room_number}</td><td>${row.room_type_name}</td>
                <td>${row.total_reservations}</td><td>${row.total_nights_booked}</td>
            </tr>`;
        case 'customer':
            return `<tr>
                <td>${row.name}</td><td>${row.phone}</td><td>${row.email ?? '-'}</td>
                <td>${row.total_reservations}</td><td>${formatRupiah(row.total_spent)}</td>
            </tr>`;
    }
}

function renderSummary(type, summary) {
    const area = document.getElementById('summaryArea');
    if (!summary) { area.innerHTML = ''; return; }

    if (type === 'revenue') {
        const methodBadges = summary.by_method.map(m => `<span class="badge bg-secondary me-1">${m.method.toUpperCase()}: ${formatRupiah(m.total)}</span>`).join('');
        area.innerHTML = `<div class="alert alert-info mb-0">
            <strong>Total Pendapatan: ${formatRupiah(summary.total)}</strong><br>${methodBadges}
        </div>`;
    } else if (type === 'reservation') {
        const badges = summary.map(s => `<span class="badge bg-secondary me-1">${s.status}: ${s.total}</span>`).join('');
        area.innerHTML = `<div class="alert alert-info mb-0">${badges}</div>`;
    } else if (type === 'room') {
        const badges = summary.map(s => `<span class="badge bg-secondary me-1">${s.status}: ${s.total}</span>`).join('');
        area.innerHTML = `<div class="alert alert-info mb-0"><strong>Status Kamar Saat Ini:</strong><br>${badges}</div>`;
    } else {
        area.innerHTML = '';
    }
}

async function loadReport(type) {
    currentType = type;
    const { start, end } = getDates();

    document.getElementById('reportTitle').innerText = reportMeta[type].title;

    const headRow = reportMeta[type].headers.map(h => `<th>${h}</th>`).join('');
    document.getElementById('reportTableHead').innerHTML = `<tr>${headRow}</tr>`;
    document.getElementById('reportTableBody').innerHTML = `<tr><td colspan="${reportMeta[type].headers.length}" class="text-center text-muted">Memuat...</td></tr>`;

    const res = await fetch(`${baseUrl}reports/data/${type}?start_date=${start}&end_date=${end}`);
    const result = await res.json();

    if (!res.ok) {
        document.getElementById('reportTableBody').innerHTML = `<tr><td colspan="${reportMeta[type].headers.length}" class="text-center text-danger">${result.message}</td></tr>`;
        return;
    }

    renderSummary(type, result.summary);

    document.getElementById('reportTableBody').innerHTML = result.rows.length
        ? result.rows.map(row => renderRow(type, row)).join('')
        : `<tr><td colspan="${reportMeta[type].headers.length}" class="text-center text-muted">Tidak ada data pada periode ini.</td></tr>`;

    updateExportLinks();
}

function updateExportLinks() {
    const { start, end } = getDates();
    document.getElementById('btnExportExcel').href = `${baseUrl}reports/export-excel/${currentType}?start_date=${start}&end_date=${end}`;
    document.getElementById('btnExportPdf').href = `${baseUrl}reports/export-pdf/${currentType}?start_date=${start}&end_date=${end}`;
}

document.querySelectorAll('#reportTabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('#reportTabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        loadReport(this.dataset.type);
    });
});

document.getElementById('filterForm').addEventListener('submit', function (e) {
    e.preventDefault();
    loadReport(currentType);
});

// Load laporan pertama saat halaman dibuka
loadReport('revenue');
</script>
<?= $this->endSection() ?>