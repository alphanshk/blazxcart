<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();
$userId = current_user()['id'];
$stmt = $pdo->prepare('SELECT id, total_amount, status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

render_header('Order History');
?>
<h4>Order Tracking / History</h4>
<div class="table-responsive"><table class="table table-striped"><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th></tr>
<?php foreach ($orders as $o): ?>
<tr><td><?= (int)$o['id'] ?></td><td>$<?= number_format((float)$o['total_amount'],2) ?></td><td><span class="badge text-bg-info"><?= h($o['status']) ?></span></td><td><?= h($o['created_at']) ?></td></tr>
<?php endforeach; ?>
</table></div>
<a href="<?= h(base_url('user/home.php')) ?>" class="btn btn-outline-dark">Continue Shopping</a>
<?php render_footer(); ?>
