<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Tipe Kamar</h6>
            <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Tipe Kamar
            </button>
        </div>

        <table id="roomTypeTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kapasitas</th>
                    <th>Harga/Malam</th>
                    <th>Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="roomTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="roomTypeForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h6 class="modal-title" id="roomTypeModalTitle">Tambah Tipe Kamar</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rtId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Tipe Kamar</label>
                            <input type="text" class="form-control" id="rtName" name="name" required>
                            <div class="invalid-feedback" id="err_name"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kapasitas (orang)</label>
                            <input type="number" class="form-control" id="rtCapacity" name="capacity" min="1" required>
                            <div class="invalid-feedback" id="err_capacity"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Harga / Malam</label>
                            <input type="number" class="form-control" id="rtPrice" name="price" min="0" step="1000" required>
                            <div class="invalid-feedback" id="err_price"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="rtDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto (opsional, max 2MB)</label>
                            <input type="file" class="form-control" id="rtPhoto" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="rtIsActive" name="is_active" value="1" checked>
                                <label class="form-check-label" for="rtIsActive">Aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fasilitas</label>
                            <div id="facilityCheckboxes" class="border rounded p-2 d-flex flex-wrap gap-3">
                                <?php foreach ($facilities as $f): ?>
                                    <div class="form-check">
                                        <input class="form-check-input facility-cb" type="checkbox" value="<?= $f['id'] ?>" id="fac<?= $f['id'] ?>">
                                        <label class="form-check-label" for="fac<?= $f['id'] ?>"><?= esc($f['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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

const table = $('#roomTypeTable').DataTable({
    ajax: { url: baseUrl + 'master/room-types/list', dataSrc: 'data' },
    columns: [
        { data: 'name' },
        { data: 'capacity', render: c => c + ' orang' },
        { data: 'price', render: p => 'Rp ' + Number(p).toLocaleString('id-ID') },
        { data: 'is_active', render: a => a == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(${JSON.stringify(row)})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRoomType(${id})"><i class="bi bi-trash"></i></button>
            `
        }
    ]
});

function resetFacilityCheckboxes() {
    document.querySelectorAll('.facility-cb').forEach(cb => cb.checked = false);
}

function clearErrors() {
    document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = '');
}

function openCreateModal() {
    document.getElementById('roomTypeForm').reset();
    document.getElementById('rtId').value = '';
    document.getElementById('roomTypeModalTitle').innerText = 'Tambah Tipe Kamar';
    resetFacilityCheckboxes();
    clearErrors();
    new bootstrap.Modal(document.getElementById('roomTypeModal')).show();
}

async function openEditModal(row) {
    document.getElementById('rtId').value = row.id;
    document.getElementById('rtName').value = row.name;
    document.getElementById('rtCapacity').value = row.capacity;
    document.getElementById('rtPrice').value = row.price;
    document.getElementById('rtDescription').value = row.description ?? '';
    document.getElementById('rtIsActive').checked = row.is_active == 1;
    document.getElementById('roomTypeModalTitle').innerText = 'Edit Tipe Kamar';
    clearErrors();
    resetFacilityCheckboxes();

    const res = await fetch(`${baseUrl}master/room-types/facilities/${row.id}`);
    const data = await res.json();
    data.facility_ids.forEach(fid => {
        const cb = document.getElementById('fac' + fid);
        if (cb) cb.checked = true;
    });

    new bootstrap.Modal(document.getElementById('roomTypeModal')).show();
}

document.getElementById('roomTypeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('rtId').value;
    const url = id ? `${baseUrl}master/room-types/update/${id}` : `${baseUrl}master/room-types/create`;

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);
    if (!document.getElementById('rtIsActive').checked) formData.set('is_active', '0');

    document.querySelectorAll('.facility-cb:checked').forEach(cb => formData.append('facility_ids[]', cb.value));

    const res = await fetch(url, { method: 'POST', body: formData });
    const result = await res.json();

    if (res.status === 422) {
        for (const field in result.errors) {
            const el = document.getElementById('err_' + field);
            if (el) el.innerText = result.errors[field];
        }
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('roomTypeModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function deleteRoomType(id) {
    Swal.fire({
        title: 'Hapus tipe kamar ini?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        const res = await fetch(`${baseUrl}master/room-types/delete/${id}`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Gagal', data.message, 'error'); return; }
        table.ajax.reload();
        Swal.fire('Terhapus', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>