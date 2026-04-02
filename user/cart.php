<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();
$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $pid = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
    }
    if ($action === 'update') {
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $_SESSION['cart'][$pid] = $qty;
    }
    set_flash('success', 'Cart updated.');
    redirect('/user/cart.php');
}

$items = [];
$total = 0.0;
if ($cart) {
    $ids = array_keys($cart);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE id IN ($ph)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $qty = min($cart[$row['id']] ?? 1, (int) $row['stock']);
        $line = $qty * (float) $row['price'];
        $items[] = ['product' => $row, 'qty' => $qty, 'line' => $line];
        $total += $line;
    }
}

render_header('Your Cart');
?>
<h4 class="mb-3">Shopping Cart</h4>
<div class="table-responsive">
<table class="table table-striped">
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Line Total</th><th></th></tr>
<?php foreach ($items as $item): $p = $item['product']; ?>
<tr>
<td><?= h($p['name']) ?></td>
<td>$<?= number_format((float)$p['price'],2) ?></td>
<td>
<form method="POST" class="d-flex gap-2">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
<input type="number" class="form-control" style="width:100px" name="quantity" min="1" max="<?= (int)$p['stock'] ?>" value="<?= (int)$item['qty'] ?>">
<button class="btn btn-sm btn-dark">Update</button>
</form>
</td>
<td>$<?= number_format($item['line'],2) ?></td>
<td>
<form method="POST"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
<div class="d-flex justify-content-between align-items-center">
<h5>Total: $<?= number_format($total,2) ?></h5>
<a href="<?= h(base_url('/user/checkout.php')) ?>" class="btn btn-success <?= $items ? '' : 'disabled' ?>">Proceed to Checkout</a>
</div>
<?php render_footer(); ?>
