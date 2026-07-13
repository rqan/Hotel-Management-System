<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabReady">Siap Check Out</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabHistory">Riwayat Hari Ini</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabReady">
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="readyTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No. Booking</th>
                            <th>Customer</th>
                            <th>Kamar</th>
                            <th>Check In</th>
                            <th>Malam</th>
                            <th width="140">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabHistory">
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="historyTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No. Booking</th>
                            <th>Customer</th>
                            <th>Kamar</th>
                            <th>Waktu Check Out</th>
                            <th>Total Biaya</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI CHECK OUT -->
<div class="modal fade" id="checkOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="checkOutForm">
                <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Check Out</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Booking: <strong id="coBookingNumber"></strong></p>

                    <table class="table table-sm">
                        <tr><td>Malam</td><td class="text-end" id="coNights"></td></tr>
                        <tr><td>Harga/Malam</td><td class="text-end" id="coRoomPrice"></td></tr>
                        <tr><td>Subtotal</td><td class="text-end" id="coSubtotal"></td></tr>
                        <tr><td>Pajak (<span id="coTaxRate"></span>%)</td><td class="text-end" id="coTaxAmount"></td></tr>
                        <tr><td>Service Charge (<span id="coScRate"></span>%)</td><td class="text-end" id="coScAmount"></td></tr>
                        <tr class="fw-bold border-top"><td>Total</td><td class="text-end" id="coTotal"></td></tr>
                    </table>

                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea class="form-control" id="coNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Proses Check Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const baseUrl = '<?= base_url() ?>';
const csrfName = '<?= csrf_token() ?>';
let csrfHash = '<?= csrf_hash() ?>';
let currentReservationId = null;

function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

const readyTable = $('#readyTable').DataTable({
    ajax: { url: baseUrl + 'checkout/ready-list', dataSrc: 'data' },
    columns: [
        { data: 'booking_number' },
        { data: 'customer_name', render: (n, t, row) => `${n}<br><small class="text-muted">${row.customer_phone}</small>` },
        { data: 'room_number', render: (r, t, row) => `${r} (${row.room_type_name})` },
        { data: 'check_in_date', render: d => new Date(d).toLocaleDateString('id-ID') },
        { data: 'nights' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-danger" onclick="openCheckOutModal(${id}, '${row.booking_number}')">
                    <i class="bi bi-box-arrow-right"></i> Check Out
                </button>
            `
        }
    ]
});

const historyTable = $('#historyTable').DataTable({
    ajax: { url: baseUrl + 'checkout/today-list', dataSrc: 'data' },
    columns: [
        { data: 'booking_number' },
        { data: 'customer_name' },
        { data: 'room_number' },
        { data: 'checked_out_at', render: d => new Date(d).toLocaleString('id-ID') },
        { data: 'total_amount', render: t => formatRupiah(t) },
    ]
});

async function openCheckOutModal(reservationId, bookingNumber) {
    currentReservationId = reservationId;
    document.getElementById('coBookingNumber').innerText = bookingNumber;
    document.getElementById('coNotes').value = '';

    const res = await fetch(`${baseUrl}checkout/preview/${reservationId}`);
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    const d = result.data;
    document.getElementById('coNights').innerText = d.nights + ' malam';
    document.getElementById('coRoomPrice').innerText = formatRupiah(d.room_price);
    document.getElementById('coSubtotal').innerText = formatRupiah(d.subtotal);
    document.getElementById('coTaxRate').innerText = d.tax_percentage;
    document.getElementById('coTaxAmount').innerText = formatRupiah(d.tax_amount);
    document.getElementById('coScRate').innerText = d.service_charge_percentage;
    document.getElementById('coScAmount').innerText = formatRupiah(d.service_charge_amount);
    document.getElementById('coTotal').innerText = formatRupiah(d.total_amount);

    new bootstrap.Modal(document.getElementById('checkOutModal')).show();
}

document.getElementById('checkOutForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}checkout/process/${currentReservationId}`, { method: 'POST', body: formData });
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('checkOutModal')).hide();
    readyTable.ajax.reload();
    historyTable.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});
</script>
<?= $this->endSection() ?>