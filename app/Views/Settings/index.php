<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h6 class="mb-3">Pengaturan Hotel</h6>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                                <li><?= esc($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('settings/update') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Hotel</label>
                            <input type="text" class="form-control" name="hotel_name"
                                   value="<?= esc(old('hotel_name', $settings['hotel_name'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= esc(old('email', $settings['email'] ?? '')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="phone"
                                   value="<?= esc(old('phone', $settings['phone'] ?? '')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Mata Uang</label>
                            <select class="form-select" name="currency">
                                <?php $currency = old('currency', $settings['currency'] ?? 'IDR'); ?>
                                <option value="IDR" <?= $currency === 'IDR' ? 'selected' : '' ?>>IDR — Rupiah</option>
                                <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD — US Dollar</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="address" rows="2"><?= esc(old('address', $settings['address'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Pajak (%)</label>
                            <input type="number" class="form-control" name="tax_percentage" step="0.01" min="0" max="100"
                                   value="<?= esc(old('tax_percentage', $settings['tax_percentage'] ?? 0)) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Service Charge (%)</label>
                            <input type="number" class="form-control" name="service_charge_percentage" step="0.01" min="0" max="100"
                                   value="<?= esc(old('service_charge_percentage', $settings['service_charge_percentage'] ?? 0)) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zona Waktu</label>
                            <select class="form-select" name="timezone">
                                <?php $timezone = old('timezone', $settings['timezone'] ?? 'Asia/Jakarta'); ?>
                                <option value="Asia/Jakarta" <?= $timezone === 'Asia/Jakarta' ? 'selected' : '' ?>>WIB (Asia/Jakarta)</option>
                                <option value="Asia/Makassar" <?= $timezone === 'Asia/Makassar' ? 'selected' : '' ?>>WITA (Asia/Makassar)</option>
                                <option value="Asia/Jayapura" <?= $timezone === 'Asia/Jayapura' ? 'selected' : '' ?>>WIT (Asia/Jayapura)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logo Hotel</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <small class="text-muted">Max 2MB, format JPG/PNG/WEBP.</small>
                        </div>

                        <?php if (!empty($settings['logo'])): ?>
                            <div class="col-md-6">
                                <label class="form-label">Logo Saat Ini</label>
                                <div>
                                    <img src="<?= base_url('uploads/settings/' . $settings['logo']) ?>" alt="Logo Hotel" style="max-height: 60px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="bi bi-check-lg"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>