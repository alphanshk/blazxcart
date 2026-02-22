<?php
require_once __DIR__ . '/bootstrap.php';

function render_header(string $title): void
{
    $user = current_user();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> | BlazxCart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/app.css')) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= h(base_url()) ?>">BlazxCart</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user && $user['role'] === 'user'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= h(base_url('user/home.php')) ?>">Shop</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'seller'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= h(base_url('seller/dashboard.php')) ?>">Seller Dashboard</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= h(base_url('admin/dashboard.php')) ?>">Admin Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex gap-2">
                <?php if ($user): ?>
                    <span class="navbar-text text-light">Hi, <?= h($user['name']) ?></span>
                    <a class="btn btn-outline-light btn-sm" href="<?= h(base_url('auth/logout.php')) ?>">Logout</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="<?= h(base_url('auth/login.php')) ?>">Login</a>
                    <a class="btn btn-warning btn-sm" href="<?= h(base_url('auth/register.php')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container">
    <?php foreach (flashes() as $flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endforeach; ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
<?php
}
