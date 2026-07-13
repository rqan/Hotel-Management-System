<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width:500px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Profil Saya</h5>

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

                <form action="<?= base_url('profile') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= esc(old('name', $user->name)) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email', $user->email)) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= esc(old('phone', $user->phone)) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>

                    <div class="text-center mt-3">
                        <a href="<?= base_url('change-password') ?>" class="small">Ubah password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>