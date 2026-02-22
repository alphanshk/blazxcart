<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare('UPDATE users SET name=? WHERE id=?');
        $stmt->execute([$name, $me['id']]);
        $_SESSION['auth_user']['name'] = $name;
        set_flash('success', 'Profile updated.');
    }
    redirect('/user/profile.php');
}

render_header('Profile');
?>
<h4>Profile Management</h4>
<form method="POST" class="card p-3 col-md-6">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= h($me['name']) ?>"></div>
<div class="mb-2"><label class="form-label">Email</label><input class="form-control" value="<?= h($me['email']) ?>" disabled></div>
<button class="btn btn-primary">Save Changes</button>
</form>
<?php render_footer(); ?>
