<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center py-5" style="min-height:100vh;">
        <div class="card shadow-sm" style="width:100%; max-width:450px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus fs-1 text-primary"></i>
                    <h4 class="mt-2">Daftar Akun Baru</h4>
                    <p class="text-muted small">Buat akun untuk melakukan reservasi kamar</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
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

                <form action="<?= base_url('register') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= esc(old('name')) ?>" required minlength="3" autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= esc(old('phone')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimal 8 karakter.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-check"></i> Daftar Sekarang
                    </button>

                    <div class="text-center mt-3">
                        Sudah punya akun? <a href="<?= base_url('login') ?>">Login di sini</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>