<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Fasilitas</h6>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#facilityModal" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Fasilitas
            </button>
        </div>

        <table id="facilityTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Icon</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- MODAL FORM -->
<div class="modal fade" id="facilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="facilityForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="facilityModalTitle">Tambah Fasilitas</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="facilityId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Nama Fasilitas</label>
                        <input type="text" class="form-control" id="facilityName" name="name" required>
                        <div class="invalid-feedback" id="err_name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons class, mis: bi-wifi)</label>
                        <input type="text" class="form-control" id="facilityIcon" name="icon">
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

const table = $('#facilityTable').DataTable({
    ajax: { url: baseUrl + 'master/facilities/list', dataSrc: 'data' },
    columns: [
        { data: 'name' },
        { data: 'icon', render: d => d ? `<i class="bi ${d}"></i> ${d}` : '-' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(${JSON.stringify(row)})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteFacility(${id})"><i class="bi bi-trash"></i></button>
            `
        }
    ]
});

function openCreateModal() {
    document.getElementById('facilityForm').reset();
    document.getElementById('facilityId').value = '';
    document.getElementById('facilityModalTitle').innerText = 'Tambah Fasilitas';
    clearErrors();
}

function openEditModal(row) {
    document.getElementById('facilityId').value = row.id;
    document.getElementById('facilityName').value = row.name;
    document.getElementById('facilityIcon').value = row.icon ?? '';
    document.getElementById('facilityModalTitle').innerText = 'Edit Fasilitas';
    clearErrors();
    new bootstrap.Modal(document.getElementById('facilityModal')).show();
}

function clearErrors() {
    document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = '');
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

document.getElementById('facilityForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('facilityId').value;
    const url = id ? `${baseUrl}master/facilities/update/${id}` : `${baseUrl}master/facilities/create`;

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

    bootstrap.Modal.getInstance(document.getElementById('facilityModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function deleteFacility(id) {
    Swal.fire({
        title: 'Hapus fasilitas ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append(csrfName, csrfHash);

        const res = await fetch(`${baseUrl}master/facilities/delete/${id}`, { method: 'POST', body: formData });
        const data = await res.json();

        if (!res.ok) {
            Swal.fire('Gagal', data.message, 'error');
            return;
        }

        table.ajax.reload();
        Swal.fire('Terhapus', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>