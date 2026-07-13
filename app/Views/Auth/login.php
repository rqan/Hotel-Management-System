<?php
$roleName = session()->get('roleName');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="card shadow-sm" style="width:100%; max-width:400px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-building fs-1 text-primary"></i>
                    <h4 class="mt-2">Hotel Management System</h4>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('reset_link')): ?>
                    <div class="alert alert-info small">
                        Link reset (development mode):<br>
                        <a href="<?= esc(session()->getFlashdata('reset_link')) ?>">
                            <?= esc(session()->getFlashdata('reset_link')) ?>
                        </a>
                    </div>
                <?php endif; ?>

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

                <form action="<?= base_url('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email')) ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>

                    <div class="text-center mt-3">
                        <a href="<?= base_url('forgot-password') ?>" class="small">Lupa password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>