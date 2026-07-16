<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar User</h6>
            <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah User
            </button>
        </div>

        <table id="userTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No HP</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- MODAL CREATE/EDIT -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="userModalTitle">Tambah User</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                        <div class="invalid-feedback" id="err_name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                        <div class="invalid-feedback" id="err_email"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" class="form-control" id="userPhone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="userRole" name="role_id" required>
                            <?php foreach ($roles ?? [] as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= esc(ucwords(str_replace('_', ' ', $r['name']))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" id="err_role_id"></div>
                    </div>
                    <div class="mb-3" id="userPasswordWrap">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="userPassword" name="password" minlength="8">
                        <small class="text-muted">Minimal 8 karakter. Kosongkan saat edit jika tidak ingin ganti password.</small>
                        <div class="invalid-feedback" id="err_password"></div>
                    </div>
                    <div class="mb-3 form-check form-switch" id="userActiveWrap">
                        <input class="form-check-input" type="checkbox" id="userIsActive" name="is_active" value="1" checked>
                        <label class="form-check-label" for="userIsActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resetPasswordForm">
                <div class="modal-header">
                    <h6 class="modal-title">Reset Password — <span id="rpUserName"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rpUserId">
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <div class="invalid-feedback" id="err_new_password"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> Reset Password
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
const currentUserId = <?= (int) session()->get('userId') ?>;

const table = $('#userTable').DataTable({
    ajax: { url: baseUrl + 'users/list', dataSrc: 'data' },
    columns: [
        { data: 'name' },
        { data: 'email' },
        { data: 'phone', render: p => p ?? '-' },
        { data: 'role_name', render: r => `<span class="badge bg-secondary">${r.replace('_',' ').toUpperCase()}</span>` },
        { data: 'is_active', render: a => a == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>' },
        { data: 'last_login_at', render: d => d ? new Date(d).toLocaleString('id-ID') : 'Belum pernah' },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => {
                const isSelf = id == currentUserId;
                return `
                    <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(${JSON.stringify(row)})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-warning" onclick="openResetPasswordModal(${id}, '${row.name}')"><i class="bi bi-key"></i></button>
                    <button class="btn btn-sm ${row.is_active == 1 ? 'btn-outline-danger' : 'btn-outline-success'}"
                        onclick="toggleActive(${id})" ${isSelf ? 'disabled title="Tidak bisa menonaktifkan diri sendiri"' : ''}>
                        <i class="bi bi-${row.is_active == 1 ? 'x-lg' : 'check-lg'}"></i>
                    </button>
                `;
            }
        }
    ]
});

function clearErrors() { document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = ''); }

function openCreateModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModalTitle').innerText = 'Tambah User';
    document.getElementById('userPassword').setAttribute('required', 'required');
    document.getElementById('userActiveWrap').classList.remove('d-none');
    clearErrors();
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function openEditModal(row) {
    document.getElementById('userId').value = row.id;
    document.getElementById('userName').value = row.name;
    document.getElementById('userEmail').value = row.email;
    document.getElementById('userPhone').value = row.phone ?? '';
    document.getElementById('userRole').value = row.role_id;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').removeAttribute('required');
    document.getElementById('userActiveWrap').classList.add('d-none'); // status diatur lewat tombol toggle, bukan di form edit
    document.getElementById('userModalTitle').innerText = 'Edit User';
    clearErrors();
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

document.getElementById('userForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('userId').value;
    const url = id ? `${baseUrl}users/update/${id}` : `${baseUrl}users/create`;

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);
    if (!document.getElementById('userIsActive').checked) formData.set('is_active', '0');

    const res = await fetch(url, { method: 'POST', body: formData });
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

    bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});

function openResetPasswordModal(id, name) {
    document.getElementById('rpUserId').value = id;
    document.getElementById('rpUserName').innerText = name;
    document.getElementById('resetPasswordForm').reset();
    clearErrors();
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

document.getElementById('resetPasswordForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const id = document.getElementById('rpUserId').value;
    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}users/reset-password/${id}`, { method: 'POST', body: formData });
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

    bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
    Swal.fire('Berhasil', result.message, 'success');
});

function toggleActive(id) {
    Swal.fire({
        title: 'Ubah status user ini?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        const res = await fetch(`${baseUrl}users/toggle-active/${id}`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Gagal', data.message, 'error'); return; }
        table.ajax.reload();
        Swal.fire('Berhasil', data.message, 'success');
    });
}
</script>
<?= $this->endSection() ?>