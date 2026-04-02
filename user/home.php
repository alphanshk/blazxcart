<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('user');

$pdo = getPDO();

$search = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$where = ['p.stock > 0'];
$params = [];

if ($search !== '') {
    $where[] = 'p.name LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($category > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $category;
}

$countSql = 'SELECT COUNT(*) FROM products p WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
[$page, $pages, $offset, $perPage] = paginate($total, 8);

$sql = 'SELECT p.*, c.name category_name, u.name seller_name
FROM products p
LEFT JOIN categories c ON c.id=p.category_id
JOIN users u ON u.id=p.seller_id
WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));

    $productStmt = $pdo->prepare('SELECT id, stock FROM products WHERE id=? LIMIT 1');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    if (!$product || $qty > (int) $product['stock']) {
        set_flash('danger', 'Invalid quantity for selected product.');
    } else {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
        set_flash('success', 'Added to cart.');
    }
    redirect('/user/home.php');
}

render_header('Shop');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-0">Browse Products</h4><small class="text-muted">Find products from approved sellers</small></div>
    <div class="d-flex gap-2">
        <a href="<?= h(base_url('/user/cart.php')) ?>" class="btn btn-dark">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
        <a href="<?= h(base_url('/user/orders.php')) ?>" class="btn btn-outline-dark">Order History</a>
        <a href="<?= h(base_url('/user/profile.php')) ?>" class="btn btn-outline-secondary">Profile</a>
    </div>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= h($search) ?>" placeholder="Search products..."></div>
    <div class="col-md-3"><select class="form-select" name="category"><option value="0">All categories</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $category===(int)$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
</form>
<div class="row g-3">
<?php foreach ($products as $p): ?>
    <div class="col-md-3">
        <div class="card product-card h-100">
            <?php if ($p['image']): ?><img src="<?= h(base_url('/uploads/' . $p['image'])) ?>" class="card-img-top" alt="<?= h($p['name']) ?>"><?php endif; ?>
            <div class="card-body">
                <h6><?= h($p['name']) ?></h6>
                <small class="text-muted"><?= h($p['category_name'] ?? 'Uncategorized') ?> by <?= h($p['seller_name']) ?></small>
                <p class="mt-2 mb-1 fw-bold">$<?= number_format((float)$p['price'],2) ?></p>
                <p class="small">Stock: <?= (int)$p['stock'] ?></p>
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <input type="number" name="quantity" min="1" max="<?= (int)$p['stock'] ?>" class="form-control form-control-sm" value="1">
                    <button class="btn btn-sm btn-dark">Add</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<nav class="mt-4"><ul class="pagination">
<?php for ($i=1; $i <= $pages; $i++): ?>
<li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&category=<?= $category ?>&page=<?= $i ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul></nav>
<?php render_footer(); ?>
