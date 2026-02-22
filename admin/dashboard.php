<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('admin');

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'seller_status') {
        $sellerId = (int) ($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        if (in_array($status, ['active', 'blocked', 'pending'], true) && $sellerId > 0) {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'seller'");
            $stmt->execute([$status, $sellerId]);
            set_flash('success', 'Seller status updated.');
        }
    }

    if ($action === 'user_status') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if (in_array($status, ['active', 'blocked'], true) && $userId > 0) {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'");
            $stmt->execute([$status, $userId]);
            set_flash('success', 'User status updated.');
        }
    }

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->execute([$name]);
            set_flash('success', 'Category added.');
        }
    }

    redirect('/admin/dashboard.php');
}

$stats = [
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'sellers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn(),
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ('paid','shipped','delivered')")->fetchColumn(),
];

$sellers = $pdo->query("SELECT id, name, email, status, created_at FROM users WHERE role='seller' ORDER BY created_at DESC")->fetchAll();
$users = $pdo->query("SELECT id, name, email, status, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 20")->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$orders = $pdo->query("SELECT o.id, o.total_amount, o.status, o.created_at, u.name AS customer FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 30")->fetchAll();

render_header('Admin Dashboard');
?>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-md-2">
            <div class="card"><div class="card-body"><small><?= h(ucfirst($label)) ?></small><h5><?= h((string)$value) ?></h5></div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <h5>Seller Approval / Block</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <tr><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
                <?php foreach ($sellers as $seller): ?>
                <tr>
                    <td><?= h($seller['name']) ?></td><td><?= h($seller['email']) ?></td><td><?= h($seller['status']) ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="seller_status">
                            <input type="hidden" name="user_id" value="<?= (int)$seller['id'] ?>">
                            <select name="status" class="form-select form-select-sm">
                                <option <?= $seller['status']==='pending'?'selected':'' ?> value="pending">Pending</option>
                                <option <?= $seller['status']==='active'?'selected':'' ?> value="active">Active</option>
                                <option <?= $seller['status']==='blocked'?'selected':'' ?> value="blocked">Blocked</option>
                            </select>
                            <button class="btn btn-sm btn-dark">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <h5>Manage Categories</h5>
        <form method="POST" class="mb-3 d-flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_category">
            <input class="form-control" name="name" placeholder="New category" required>
            <button class="btn btn-primary">Add</button>
        </form>
        <ul class="list-group">
            <?php foreach ($categories as $cat): ?><li class="list-group-item"><?= h($cat['name']) ?></li><?php endforeach; ?>
        </ul>
    </div>
</div>

<h5 class="mt-4">Recent Orders</h5>
<div class="table-responsive mb-4">
<table class="table table-sm table-striped"><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
<?php foreach ($orders as $order): ?>
<tr><td><?= (int)$order['id'] ?></td><td><?= h($order['customer']) ?></td><td>$<?= number_format((float)$order['total_amount'],2) ?></td><td><?= h($order['status']) ?></td><td><?= h($order['created_at']) ?></td></tr>
<?php endforeach; ?></table>
</div>

<h5>Users</h5>
<div class="table-responsive">
<table class="table table-sm table-striped"><tr><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
<?php foreach ($users as $u): ?>
<tr>
<td><?= h($u['name']) ?></td><td><?= h($u['email']) ?></td><td><?= h($u['status']) ?></td>
<td>
<form method="POST" class="d-flex gap-1">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="action" value="user_status">
<input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
<select name="status" class="form-select form-select-sm"><option value="active">Active</option><option value="blocked">Blocked</option></select>
<button class="btn btn-sm btn-dark">Save</button>
</form>
</td></tr>
<?php endforeach; ?>
</table></div>
<?php render_footer(); ?>
