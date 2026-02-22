<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    set_flash('warning', 'Cart is empty.');
    redirect('/user/home.php');
}

$ids = array_keys($cart);
$ph = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, price, stock FROM products WHERE id IN ($ph) FOR UPDATE");
$pdo->beginTransaction();
$stmt->execute($ids);
$products = $stmt->fetchAll();

$map = [];
foreach ($products as $p) {
    $map[$p['id']] = $p;
}

$total = 0;
$validLines = [];
foreach ($cart as $id => $qty) {
    if (!isset($map[$id])) {
        continue;
    }
    $available = (int) $map[$id]['stock'];
    $qty = max(1, min((int) $qty, $available));
    if ($qty <= 0) {
        continue;
    }
    $price = (float) $map[$id]['price'];
    $total += $qty * $price;
    $validLines[] = ['id' => $id, 'qty' => $qty, 'price' => $price];
}

if (!$validLines) {
    $pdo->rollBack();
    set_flash('danger', 'No valid items in cart.');
    redirect('/user/cart.php');
}

$userId = current_user()['id'];
$orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'paid', NOW())");
$orderStmt->execute([$userId, $total]);
$orderId = (int) $pdo->lastInsertId();

$itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
$stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

foreach ($validLines as $line) {
    $itemStmt->execute([$orderId, $line['id'], $line['qty'], $line['price']]);
    $stockStmt->execute([$line['qty'], $line['id'], $line['qty']]);
}

$pdo->commit();
unset($_SESSION['cart']);
set_flash('success', 'Order placed successfully. Order #' . $orderId);
redirect('/user/orders.php');
