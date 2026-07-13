<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Customer</h6>
            <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Customer
            </button>
        </div>

        <table id="customerTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No HP</th>
                    <th>Identitas</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="customerForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="customerModalTitle">Tambah Customer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="custId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" id="custName" name="name" required>
                            <div class="invalid-feedback" id="err_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="custEmail" name="email">
                            <div class="invalid-feedback" id="err_email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" class="form-control" id="custPhone" name="phone" required>
                            <div class="invalid-feedback" id="err_phone"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jenis Identitas</label>
                            <select class="form-select" id="custIdType" name="identity_type">
                                <option value="">-</option>
                                <option value="ktp">KTP</option>
                                <option value="sim">SIM</option>
                                <option value="passport">Passport</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. Identitas</label>
                            <input type="text" class="form-control" id="custIdNumber" name="identity_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="custAddress" name="address" rows="2"></textarea>
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

const table = $('#customerTable').DataTable({
    ajax: { url: baseUrl + 'master/customers/list', dataSrc: 'data' },
    columns: [
        { data: 'name' },
        { data: 'email', render: e => e ?? '-' },
        { data: 'phone' },
        { data: 'identity_number', render: (n, t, row) => n ? `${row.identity_type?.toUpperCase()}: ${n}` : '-' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(${JSON.stringify(row)})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(${id})"><i class="bi bi-trash"></i></button>
            `
        }
    ]
});

function clearErrors() { document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = ''); }

function openCreateModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('custId').value = '';
    document.getElementById('customerModalTitle').innerText = 'Tambah Customer';
    clearErrors();
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

function openEditModal(row) {
    document.getElementById('custId').value = row.id;
    document.getElementById('custName').value = row.name;
    document.getElementById('custEmail').value = row.email ?? '';
    document.getElementById('custPhone').value = row.phone;
    document.getElementById('custIdType').value = row.identity_type ?? '';
    document.getElementById('custIdNumber').value = row.identity_number ?? '';
    document.getElementById('custAddress').value = row.address ?? '';
    document.getElementById('customerModalTitle').innerText = 'Edit Customer';
    clearErrors();
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

document.getElementById('customerForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('custId').value;
    const url = id ? `${baseUrl}master/customers/update/${id}` : `${baseUrl}master/customers/create`;

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

    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function deleteCustomer(id) {
    Swal.fire({
        title: 'Hapus customer ini?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        const res = await fetch(`${baseUrl}master/customers/delete/${id}`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Gagal', data.message, 'error'); return; }
        table.ajax.reload();
        Swal.fire('Terhapus', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>