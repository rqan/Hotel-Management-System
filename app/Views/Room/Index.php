<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Kamar</h6>
            <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Kamar
            </button>
        </div>

        <table id="roomTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>No. Kamar</th>
                    <th>Tipe</th>
                    <th>Lantai</th>
                    <th>Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="roomForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="roomModalTitle">Tambah Kamar</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="roomId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Nomor Kamar</label>
                        <input type="text" class="form-control" id="roomNumber" name="room_number" required>
                        <div class="invalid-feedback" id="err_room_number"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Kamar</label>
                        <select class="form-select" id="roomTypeId" name="room_type_id" required>
                            <option value="">-- Pilih Tipe --</option>
                            <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?= $rt['id'] ?>"><?= esc($rt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" id="err_room_type_id"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lantai</label>
                        <input type="text" class="form-control" id="roomFloor" name="floor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="roomStatus" name="status" required>
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="occupied" disabled>Occupied (otomatis via Check In)</option>
                        </select>
                        <div class="invalid-feedback" id="err_status"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="roomNotes" name="notes" rows="2"></textarea>
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
    available: 'success', occupied: 'danger', reserved: 'warning',
    cleaning: 'info', maintenance: 'secondary'
};

const table = $('#roomTable').DataTable({
    ajax: { url: baseUrl + 'master/rooms/list', dataSrc: 'data' },
    columns: [
        { data: 'room_number' },
        { data: 'room_type_name' },
        { data: 'floor', render: f => f ?? '-' },
        { data: 'status', render: s => `<span class="badge bg-${statusBadge[s] ?? 'secondary'}">${s}</span>` },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(${JSON.stringify(row)})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(${id})"><i class="bi bi-trash"></i></button>
            `
        }
    ]
});

function clearErrors() { document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = ''); }

function openCreateModal() {
    document.getElementById('roomForm').reset();
    document.getElementById('roomId').value = '';
    document.getElementById('roomModalTitle').innerText = 'Tambah Kamar';
    clearErrors();
    new bootstrap.Modal(document.getElementById('roomModal')).show();
}

function openEditModal(row) {
    document.getElementById('roomId').value = row.id;
    document.getElementById('roomNumber').value = row.room_number;
    document.getElementById('roomTypeId').value = row.room_type_id;
    document.getElementById('roomFloor').value = row.floor ?? '';
    document.getElementById('roomStatus').value = row.status;
    document.getElementById('roomNotes').value = row.notes ?? '';
    document.getElementById('roomModalTitle').innerText = 'Edit Kamar';
    clearErrors();
    new bootstrap.Modal(document.getElementById('roomModal')).show();
}

document.getElementById('roomForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('roomId').value;
    const url = id ? `${baseUrl}master/rooms/update/${id}` : `${baseUrl}master/rooms/create`;

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(url, { method: 'POST', body: formData });
    const result = await res.json();

    if (res.status === 422) {
        for (const field in result.errors) {
            const el = document.getElementById('err_' + field);
            if (el) el.innerText = result.errors[field];
        }
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function deleteRoom(id) {
    Swal.fire({
        title: 'Hapus kamar ini?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        const res = await fetch(`${baseUrl}master/rooms/delete/${id}`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Gagal', data.message, 'error'); return; }
        table.ajax.reload();
        Swal.fire('Terhapus', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>