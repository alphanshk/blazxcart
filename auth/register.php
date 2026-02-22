<?php
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $role = in_array($role, ['user', 'seller'], true) ? $role : 'user';

    if ($name === '' || !$email || strlen($password) < 6) {
        set_flash('danger', 'Name, valid email, and password >= 6 chars are required.');
        redirect(base_url('auth/register.php'));
    }

    $pdo = getPDO();
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        set_flash('danger', 'Email already exists.');
        redirect(base_url('auth/register.php'));
    }

    $status = $role === 'seller' ? 'pending' : 'active';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $hash, $role, $status]);

    set_flash('success', $role === 'seller' ? 'Seller account created and awaiting admin approval.' : 'Registration successful. Please login.');
    redirect(base_url('auth/login.php'));
}

render_header('Register');
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3>Create BlazxCart Account</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="6" required></div>
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select class="form-select" name="role">
                            <option value="user">User (buyer)</option>
                            <option value="seller">Seller (needs approval)</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
