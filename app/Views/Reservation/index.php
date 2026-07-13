<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Reservasi</h6>
            <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Reservasi
            </button>
        </div>

        <table id="reservationTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>No. Booking</th>
                    <th>Customer</th>
                    <th>Kamar</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Malam</th>
                    <th>Status</th>
                    <th width="160">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- MODAL CREATE (Staff) -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="reservationForm">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Reservasi</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach ($customers ?? [] as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="err_customer_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Kamar</label>
                            <select class="form-select" id="rsvRoomType" required>
                                <option value="">-- Pilih Tipe --</option>
                                <?php foreach ($roomTypes ?? [] as $rt): ?>
                                    <option value="<?= $rt['id'] ?>"><?= esc($rt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Check In</label>
                            <input type="date" class="form-control" id="rsvCheckIn" name="check_in_date" required>
                            <div class="invalid-feedback" id="err_check_in_date"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Check Out</label>
                            <input type="date" class="form-control" id="rsvCheckOut" name="check_out_date" required>
                            <div class="invalid-feedback" id="err_check_out_date"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Tamu</label>
                            <input type="number" class="form-control" name="guests" min="1" value="1" required>
                            <div class="invalid-feedback" id="err_guests"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kamar Tersedia</label>
                            <select class="form-select" name="room_id" id="rsvRoomId" required>
                                <option value="">-- Pilih Tipe & Tanggal dulu --</option>
                            </select>
                            <div class="invalid-feedback" id="err_room_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
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

const statusBadge = {
    pending: 'warning', confirmed: 'primary', checked_in: 'success',
    checked_out: 'secondary', cancelled: 'danger', no_show: 'dark'
};

const table = $('#reservationTable').DataTable({
    ajax: { url: baseUrl + 'reservation/list', dataSrc: 'data' },
    columns: [
        { data: 'booking_number' },
        { data: 'customer_name' },
        { data: 'room_number', render: (r, t, row) => `${r} (${row.room_type_name})` },
        { data: 'check_in_date', render: function(d){ return (d? (new Date(d)).toLocaleDateString('id-ID') : ''); } },
        { data: 'check_out_date', render: function(d){ return (d? (new Date(d)).toLocaleDateString('id-ID') : ''); } },
        { data: 'nights' },
        { data: 'status', render: s => `<span class="badge bg-${statusBadge[s] ?? 'secondary'}">${s}</span>` },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => {
                if (['pending', 'confirmed'].includes(row.status)) {
                    return `
                        <button class="btn btn-sm btn-outline-success" onclick="updateStatus(${id}, 'confirmed')" title="Confirm"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="updateStatus(${id}, 'cancelled')" title="Cancel"><i class="bi bi-x-lg"></i></button>
                    `;
                }
                return '-';
            }
        }
    ]
});

// Ambil kamar available saat tipe/tanggal berubah
async function refreshAvailableRooms() {
    const roomTypeId = document.getElementById('rsvRoomType').value;
    const checkIn = document.getElementById('rsvCheckIn').value;
    const checkOut = document.getElementById('rsvCheckOut').value;
    const roomSelect = document.getElementById('rsvRoomId');

    if (!roomTypeId || !checkIn || !checkOut) return;

    roomSelect.innerHTML = '<option value="">Memuat...</option>';

    const res = await fetch(`${baseUrl}reservation/available-rooms?room_type_id=${roomTypeId}&check_in_date=${checkIn}&check_out_date=${checkOut}`);
    const data = await res.json();

    if (!data.data || data.data.length === 0) {
        roomSelect.innerHTML = '<option value="">Tidak ada kamar tersedia</option>';
        return;
    }

    roomSelect.innerHTML = '<option value="">-- Pilih Kamar --</option>' +
        data.data.map(r => `<option value="${r.id}">${r.room_number}</option>`).join('');
}

['rsvRoomType', 'rsvCheckIn', 'rsvCheckOut'].forEach(id => {
    document.getElementById(id).addEventListener('change', refreshAvailableRooms);
});

function clearErrors() { document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = ''); }

function openCreateModal() {
    document.getElementById('reservationForm').reset();
    clearErrors();
    new bootstrap.Modal(document.getElementById('reservationModal')).show();
}

document.getElementById('reservationForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}reservation/create`, { method: 'POST', body: formData });
    const result = await res.json();

    if (res.status === 422) {
        for (const field in result.errors) {
            const el = document.getElementById('err_' + field);
            if (el) el.innerText = result.errors[field];
        }
        return;
    }

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('reservationModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function updateStatus(id, status) {
    const label = status === 'confirmed' ? 'konfirmasi' : 'batalkan';
    Swal.fire({
        title: `Yakin ${label} reservasi ini?`, icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('status', status);
        const res = await fetch(`${baseUrl}reservation/update-status/${id}`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Gagal', data.message, 'error'); return; }
        table.ajax.reload();
        Swal.fire('Berhasil', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>