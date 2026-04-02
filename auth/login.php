<?php
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in()) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        set_flash('danger', 'Valid email and password are required.');
        redirect('/auth/login.php');
    }

    $stmt = getPDO()->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('danger', 'Invalid credentials.');
        redirect('/auth/login.php');
    }

    if ($user['status'] !== 'active') {
        set_flash('warning', 'Your account is currently ' . $user['status'] . '.');
        redirect('/auth/login.php');
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
    ];

    if ($user['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    }
    if ($user['role'] === 'seller') {
        redirect('/seller/dashboard.php');
    }
    redirect('/user/home.php');
}

render_header('Login');
?>
<div class="page-hero">
    <h4 class="mb-1">One Login for BlazxCart</h4>
    <p class="text-muted mb-0">Access admin, seller, and buyer portals from one secure sign-in.</p>
</div>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="mb-3">One Login for BlazxCart</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-dark w-100">Login</button>
                </form>
                <p class="small mt-3 mb-0">No account? <a href="<?= h(base_url('/auth/register.php')) ?>">Register as User/Seller</a></p>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
