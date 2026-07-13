<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="mb-3">Daftar Invoice</h6>

        <table id="invoiceTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>No. Booking</th>
                    <th>Customer</th>
                    <th>Kamar</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th width="140">Aksi</th>
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
const statusBadge = { unpaid: 'danger', partial: 'warning', paid: 'success', refunded: 'secondary' };

function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

$('#invoiceTable').DataTable({
    ajax: { url: baseUrl + 'invoice/list', dataSrc: 'data' },
    columns: [
        { data: 'invoice_number' },
        { data: 'booking_number' },
        { data: 'customer_name' },
        { data: 'room_number' },
        { data: 'total_amount', render: t => formatRupiah(t) },
        { data: 'status', render: s => `<span class="badge bg-${statusBadge[s] ?? 'secondary'}">${s}</span>` },
        {
            data: 'id', orderable: false,
            render: (id) => `
                <a href="${baseUrl}invoice/view/${id}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="${baseUrl}invoice/download/${id}" class="btn btn-sm btn-outline-success" title="Download PDF">
                    <i class="bi bi-download"></i>
                </a>
            `
        }
    ]
});
</script>
<?= $this->endSection() ?>