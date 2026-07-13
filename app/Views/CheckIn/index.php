<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tabReady">Siap Check In</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabHistory">Riwayat Hari Ini</a>
    </li>
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
                            <th>Rencana Check In</th>
                            <th>Tamu</th>
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
                            <th>Waktu Check In</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI CHECK IN -->
<div class="modal fade" id="checkInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="checkInForm">
                <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Check In</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Check-in untuk booking <strong id="ciBookingNumber"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea class="form-control" id="ciNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right"></i> Proses Check In
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

const readyTable = $('#readyTable').DataTable({
    ajax: { url: baseUrl + 'checkin/ready-list', dataSrc: 'data' },
    columns: [
        { data: 'booking_number' },
        { data: 'customer_name', render: (n, t, row) => `${n}<br><small class="text-muted">${row.customer_phone}</small>` },
        { data: 'room_number', render: (r, t, row) => `${r} (${row.room_type_name})` },
        { data: 'check_in_date', render: d => new Date(d).toLocaleDateString('id-ID') },
        { data: 'guests' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-success" onclick="openCheckInModal(${id}, '${row.booking_number}')">
                    <i class="bi bi-box-arrow-in-right"></i> Check In
                </button>
            `
        }
    ]
});

const historyTable = $('#historyTable').DataTable({
    ajax: { url: baseUrl + 'checkin/today-list', dataSrc: 'data' },
    columns: [
        { data: 'booking_number' },
        { data: 'customer_name' },
        { data: 'room_number' },
        { data: 'checked_in_at', render: d => new Date(d).toLocaleString('id-ID') },
        { data: 'notes', render: n => n || '-' },
    ]
});

function openCheckInModal(reservationId, bookingNumber) {
    currentReservationId = reservationId;
    document.getElementById('ciBookingNumber').innerText = bookingNumber;
    document.getElementById('ciNotes').value = '';
    new bootstrap.Modal(document.getElementById('checkInModal')).show();
}

document.getElementById('checkInForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}checkin/process/${currentReservationId}`, { method: 'POST', body: formData });
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('checkInModal')).hide();
    readyTable.ajax.reload();
    historyTable.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});
</script>
<?= $this->endSection() ?>