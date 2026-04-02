<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    set_flash('warning', 'Cart is empty.');
    redirect('/user/home.php');
}

$ids = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$productMap = [];
foreach ($products as $product) {
    $productMap[(int) $product['id']] = $product;
}

$validLines = [];
$total = 0.0;
foreach ($cart as $id => $qty) {
    $id = (int) $id;
    if (!isset($productMap[$id])) {
        continue;
    }

    $available = (int) $productMap[$id]['stock'];
    $qty = max(1, min((int) $qty, $available));
    if ($qty <= 0) {
        continue;
    }

    $price = (float) $productMap[$id]['price'];
    $lineTotal = $qty * $price;
    $validLines[] = [
        'id' => $id,
        'name' => $productMap[$id]['name'],
        'qty' => $qty,
        'price' => $price,
        'line_total' => $lineTotal,
    ];
    $total += $lineTotal;
}

if (!$validLines) {
    set_flash('danger', 'No valid items in cart.');
    redirect('/user/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $pdo->beginTransaction();

        $lockStmt = $pdo->prepare("SELECT id, stock, price FROM products WHERE id IN ($placeholders) FOR UPDATE");
        $lockStmt->execute($ids);
        $lockedRows = $lockStmt->fetchAll();

        $lockedMap = [];
        foreach ($lockedRows as $row) {
            $lockedMap[(int) $row['id']] = $row;
        }

        foreach ($validLines as $line) {
            if (!isset($lockedMap[$line['id']])) {
                throw new RuntimeException('One of the products is no longer available.');
            }
            if ((int) $lockedMap[$line['id']]['stock'] < $line['qty']) {
                throw new RuntimeException('Stock changed for one or more products. Please review your cart and retry.');
            }
        }

        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'paid', NOW())");
        $orderStmt->execute([current_user()['id'], $total]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

        foreach ($validLines as $line) {
            $itemStmt->execute([$orderId, $line['id'], $line['qty'], $line['price']]);
            $stockStmt->execute([$line['qty'], $line['id'], $line['qty']]);
            if ($stockStmt->rowCount() !== 1) {
                throw new RuntimeException('Could not reserve stock for all items.');
            }
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        set_flash('success', 'Order placed successfully. Order #' . $orderId);
        redirect('/user/orders.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('danger', $e->getMessage());
        redirect('/user/cart.php');
    }
}

render_header('Checkout');
?>
<div class="page-hero"><h4 class="mb-1">Secure Checkout</h4><p class="text-muted mb-0">Review your order details before placing your order.</p></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="table-responsive">
            <table class="table table-striped">
                <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Line Total</th></tr>
                <?php foreach ($validLines as $line): ?>
                    <tr>
                        <td><?= h($line['name']) ?></td>
                        <td>$<?= number_format($line['price'], 2) ?></td>
                        <td><?= (int) $line['qty'] ?></td>
                        <td>$<?= number_format($line['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5>Order Summary</h5>
                <p class="mb-3">Total: <strong>$<?= number_format($total, 2) ?></strong></p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-success w-100">Place Order</button>
                </form>
                <a href="/user/cart.php" class="btn btn-outline-dark w-100 mt-2">Back to Cart</a>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
