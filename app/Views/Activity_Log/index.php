<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Modul</label>
                <select class="form-select" id="filterModule">
                    <option value="">Semua Modul</option>
                    <?php if (!empty($modules) && is_array($modules)): ?>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= esc($m) ?>"><?= esc(ucfirst(str_replace('_', ' ', $m))) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select class="form-select" id="filterUser">
                    <option value="">Semua User</option>
                    <?php if (!empty($users) && is_array($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="filterStartDate">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="filterEndDate">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="mb-3">Riwayat Aktivitas <small class="text-muted">(maks. 1000 terbaru)</small></h6>

        <table id="logTable" class="table table-sm table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Modul</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>IP Address</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>

<script>
const baseUrl = '<?= base_url() ?>';

const actionBadge = {
    create: 'success', update: 'primary', delete: 'danger',
    login: 'info', check_in: 'success', check_out: 'warning',
    activate: 'success', deactivate: 'secondary', reset_password: 'warning',
};

const table = $('#logTable').DataTable({
    ajax: { url: baseUrl + 'activity-logs/list', dataSrc: 'data' },
    order: [[0, 'desc']],
    columns: [
        { data: 'created_at', render: d => new Date(d).toLocaleString('id-ID') },
        { data: 'user_name', render: n => n ?? '<span class="text-muted">System</span>' },
        { data: 'module', render: m => `<span class="badge bg-secondary">${m}</span>` },
        { data: 'action', render: a => `<span class="badge bg-${actionBadge[a] ?? 'dark'}">${a}</span>` },
        { data: 'description' },
        { data: 'ip_address', render: ip => ip ?? '-' },
    ]
});

function buildQuery() {
    const params = new URLSearchParams();
    const module = document.getElementById('filterModule').value;
    const userId = document.getElementById('filterUser').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    if (module) params.append('module', module);
    if (userId) params.append('user_id', userId);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    return params.toString();
}

document.getElementById('filterForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const query = buildQuery();
    table.ajax.url(`${baseUrl}activity-logs/list?${query}`).load();
});
</script>
<?= $this->endSection() ?>