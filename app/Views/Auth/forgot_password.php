<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="card shadow-sm" style="width:100%; max-width:400px;">
            <div class="card-body p-4">
                <h5 class="mb-3">Lupa Password</h5>

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

                <form action="<?= base_url('forgot-password') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Masukkan email terdaftar</label>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
                    <div class="text-center mt-3">
                        <a href="<?= base_url('login') ?>" class="small">Kembali ke login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>