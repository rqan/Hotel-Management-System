<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="mb-3">Invoice Belum Lunas</h6>

        <table id="invoiceTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>No. Booking</th>
                    <th>Customer</th>
                    <th>Kamar</th>
                    <th>Total Tagihan</th>
                    <th>Sudah Dibayar</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- MODAL DETAIL & BAYAR -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Detail Pembayaran — <span id="pmInvoiceNumber"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4"><small class="text-muted">Total Tagihan</small><div class="fw-bold" id="pmTotalAmount"></div></div>
                    <div class="col-md-4"><small class="text-muted">Sudah Dibayar</small><div class="fw-bold text-success" id="pmTotalPaid"></div></div>
                    <div class="col-md-4"><small class="text-muted">Sisa Tagihan</small><div class="fw-bold text-danger" id="pmRemaining"></div></div>
                </div>

                <h6 class="small text-uppercase text-muted">Rincian Item</h6>
                <table class="table table-sm mb-2">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Jumlah</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="pmItemsBody"></tbody>
                </table>

                <form id="pmAddItemForm" class="row g-2 mb-4 align-items-end">
                    <input type="hidden" id="pmItemInvoiceId" name="invoice_id">
                    <div class="col-md-4">
                        <label class="form-label small">Deskripsi</label>
                        <input type="text" class="form-control form-control-sm" name="description" placeholder="mis. Extra Pillow" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Qty</label>
                        <input type="number" class="form-control form-control-sm" name="quantity" value="1" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Harga Satuan</label>
                        <input type="number" class="form-control form-control-sm" name="unit_price" min="0" step="1000" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100" id="pmAddItemBtn">
                            <i class="bi bi-plus-lg"></i> Tambah Item
                        </button>
                    </div>
                </form>

                <h6 class="small text-uppercase text-muted">Riwayat Pembayaran</h6>
                <table class="table table-sm mb-4">
                    <thead><tr><th>No.</th><th>Metode</th><th>Jumlah</th><th>Waktu</th></tr></thead>
                    <tbody id="pmHistoryBody"></tbody>
                </table>

                <div id="pmPaidNotice" class="alert alert-success d-none">
                    <i class="bi bi-check-circle"></i> Invoice ini sudah lunas.
                </div>

                <form id="pmPaymentForm">
                    <input type="hidden" id="pmInvoiceId" name="invoice_id">
                    <h6 class="small text-uppercase text-muted">Tambah Pembayaran</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Metode</label>
                            <select class="form-select" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                                <option value="credit_card">Credit Card</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control" name="amount" id="pmAmountInput" min="1" step="1000" required>
                            <div class="invalid-feedback" id="err_amount"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Referensi (opsional)</label>
                            <input type="text" class="form-control" name="reference_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3" id="pmSubmitBtn">
                        <i class="bi bi-cash-coin"></i> Simpan Pembayaran
                    </button>
                </form>
            </div>
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

function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

const statusBadge = { unpaid: 'danger', partial: 'warning', paid: 'success', refunded: 'secondary' };

const table = $('#invoiceTable').DataTable({
    ajax: { url: baseUrl + 'payment/unpaid-list', dataSrc: 'data' },
    columns: [
        { data: 'invoice_number' },
        { data: 'booking_number' },
        { data: 'customer_name' },
        { data: 'room_number' },
        { data: 'total_amount', render: t => formatRupiah(t) },
        { data: 'total_paid', render: t => formatRupiah(t) },
        { data: 'remaining', render: t => formatRupiah(t) },
        { data: 'status', render: s => `<span class="badge bg-${statusBadge[s] ?? 'secondary'}">${s}</span>` },
        {
            data: 'id', orderable: false,
            render: (id, type, row) => `
                <button class="btn btn-sm btn-primary" onclick="openPaymentModal(${id})">
                    <i class="bi bi-cash-coin"></i> Bayar
                </button>
            `
        }
    ]
});

async function loadInvoiceItems(invoiceId) {
    const res = await fetch(`${baseUrl}invoice/items/${invoiceId}`);
    const result = await res.json();

    const body = document.getElementById('pmItemsBody');
    body.innerHTML = result.data.map(item => `
        <tr>
            <td>${item.description}</td>
            <td class="text-end">${item.quantity}</td>
            <td class="text-end">${formatRupiah(item.unit_price)}</td>
            <td class="text-end">${formatRupiah(item.amount)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInvoiceItem(${item.id})"><i class="bi bi-trash"></i></button></td>
        </tr>
    `).join('');
}

async function deleteInvoiceItem(itemId) {
    const formData = new FormData();
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}invoice/items/delete/${itemId}`, { method: 'POST', body: formData });
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    const invoiceId = document.getElementById('pmItemInvoiceId').value;
    await refreshInvoiceModal(invoiceId);
    table.ajax.reload();
}

async function refreshInvoiceModal(invoiceId) {
    const res = await fetch(`${baseUrl}payment/detail/${invoiceId}`);
    const result = await res.json();

    document.getElementById('pmTotalAmount').innerText = formatRupiah(result.invoice.total_amount);
    document.getElementById('pmTotalPaid').innerText = formatRupiah(result.total_paid);
    document.getElementById('pmRemaining').innerText = formatRupiah(result.remaining);
    document.getElementById('pmAmountInput').max = result.remaining;
    document.getElementById('pmAmountInput').value = result.remaining;

    await loadInvoiceItems(invoiceId);
}

async function openPaymentModal(invoiceId) {
    document.getElementById('pmInvoiceId').value = invoiceId;
    document.getElementById('pmItemInvoiceId').value = invoiceId;

    const res = await fetch(`${baseUrl}payment/detail/${invoiceId}`);
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message, 'error');
        return;
    }

    document.getElementById('pmInvoiceNumber').innerText = result.invoice.invoice_number;
    document.getElementById('pmTotalAmount').innerText = formatRupiah(result.invoice.total_amount);
    document.getElementById('pmTotalPaid').innerText = formatRupiah(result.total_paid);
    document.getElementById('pmRemaining').innerText = formatRupiah(result.remaining);
    document.getElementById('pmAmountInput').max = result.remaining;
    document.getElementById('pmAmountInput').value = result.remaining;

    await loadInvoiceItems(invoiceId);

    const historyBody = document.getElementById('pmHistoryBody');
    historyBody.innerHTML = result.payments.length
        ? result.payments.map(p => `
            <tr>
                <td>${p.payment_number}</td>
                <td>${p.method.toUpperCase()}</td>
                <td>${formatRupiah(p.amount)}</td>
                <td>${new Date(p.paid_at).toLocaleString('id-ID')}</td>
            </tr>
        `).join('')
        : '<tr><td colspan="4" class="text-muted">Belum ada pembayaran.</td></tr>';

    const isPaid = result.invoice.status === 'paid';
    document.getElementById('pmPaidNotice').classList.toggle('d-none', !isPaid);
    document.getElementById('pmPaymentForm').classList.toggle('d-none', isPaid);
    document.getElementById('pmAddItemForm').classList.toggle('d-none', isPaid);

    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

document.getElementById('pmAddItemForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}invoice/items/add`, { method: 'POST', body: formData });
    const result = await res.json();

    if (!res.ok) {
        Swal.fire('Gagal', result.message || 'Validasi gagal.', 'error');
        return;
    }

    this.reset();
    this.querySelector('[name="quantity"]').value = 1;

    const invoiceId = document.getElementById('pmItemInvoiceId').value;
    await refreshInvoiceModal(invoiceId);
    table.ajax.reload();
});

document.getElementById('pmPaymentForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    document.getElementById('err_amount').innerText = '';

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}payment/create`, { method: 'POST', body: formData });
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

    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
    table.ajax.reload();
    Swal.fire('Berhasil', result.message, 'success');
});
</script>
<?= $this->endSection() ?>