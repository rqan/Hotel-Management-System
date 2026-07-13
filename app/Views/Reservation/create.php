<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Buat Reservasi Baru</h5>

                <form id="selfBookingForm">
                    <div class="mb-3">
                        <label class="form-label">Tipe Kamar</label>
                        <select class="form-select" name="room_type_id" required>
                            <option value="">-- Pilih Tipe Kamar --</option>
                            <?php $roomTypesList = (isset($roomTypes) && is_array($roomTypes)) ? $roomTypes : []; ?>
                            <?php foreach ($roomTypesList as $rt): ?>
                                <option value="<?= $rt['id'] ?>">
                                    <?= esc($rt['name']) ?> — Rp <?= number_format($rt['price'], 0, ',', '.') ?>/malam (max <?= $rt['capacity'] ?> orang)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" id="err_room_type_id"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Check In</label>
                            <input type="date" class="form-control" name="check_in_date" min="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback" id="err_check_in_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out</label>
                            <input type="date" class="form-control" name="check_out_date" required>
                            <div class="invalid-feedback" id="err_check_out_date"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Tamu</label>
                        <input type="number" class="form-control" name="guests" min="1" value="1" required>
                        <div class="invalid-feedback" id="err_guests"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Permintaan khusus, dsb."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check"></i> Buat Reservasi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const baseUrl = '<?= base_url() ?>';
const csrfName = '<?= csrf_token() ?>';
let csrfHash = '<?= csrf_hash() ?>';

function clearErrors() { document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = ''); }

document.getElementById('selfBookingForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const formData = new FormData(this);
    formData.append(csrfName, csrfHash);

    const res = await fetch(`${baseUrl}my-reservations/store`, { method: 'POST', body: formData });
    const result = await res.json();

    if (res.status === 422 && result.errors) {
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

    Swal.fire('Berhasil', result.message, 'success').then(() => {
        window.location.href = `${baseUrl}dashboard`;
    });
});
</script>
<?= $this->endSection() ?>