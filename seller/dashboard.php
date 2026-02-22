<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('seller');

$pdo = getPDO();
$sellerId = current_user()['id'];

function upload_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Image too large (max 2MB).');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP images are allowed.');
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = __DIR__ . '/../uploads/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0 || $stock < 0 || $categoryId <= 0) {
            set_flash('danger', 'Please provide valid product details.');
            redirect('/seller/dashboard.php');
        }

        try {
            $img = upload_product_image($_FILES['image'] ?? []);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO products (seller_id, name, price, stock, image, category_id) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$sellerId, $name, $price, $stock, $img, $categoryId]);
                set_flash('success', 'Product created.');
            } else {
                if ($img) {
                    $stmt = $pdo->prepare('UPDATE products SET name=?, price=?, stock=?, image=?, category_id=? WHERE id=? AND seller_id=?');
                    $stmt->execute([$name, $price, $stock, $img, $categoryId, $id, $sellerId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE products SET name=?, price=?, stock=?, category_id=? WHERE id=? AND seller_id=?');
                    $stmt->execute([$name, $price, $stock, $categoryId, $id, $sellerId]);
                }
                set_flash('success', 'Product updated.');
            }
        } catch (RuntimeException $e) {
            set_flash('danger', $e->getMessage());
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=? AND seller_id=?');
        $stmt->execute([$id, $sellerId]);
        set_flash('success', 'Product deleted.');
    }

    redirect('/seller/dashboard.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editProduct = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=? AND seller_id=?');
    $stmt->execute([$editId, $sellerId]);
    $editProduct = $stmt->fetch();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$products = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE seller_id=? ORDER BY p.id DESC');
$products->execute([$sellerId]);
$products = $products->fetchAll();
$orders = $pdo->prepare('SELECT o.id, o.status, o.created_at, u.name customer, oi.quantity, oi.price, p.name product_name
FROM orders o
JOIN order_items oi ON oi.order_id=o.id
JOIN products p ON p.id=oi.product_id
JOIN users u ON u.id=o.user_id
WHERE p.seller_id=? ORDER BY o.created_at DESC LIMIT 50');
$orders->execute([$sellerId]);
$orders = $orders->fetchAll();

render_header('Seller Dashboard');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h5><?= $editProduct ? 'Update Product' : 'Add Product' ?></h5>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                <input type="hidden" name="id" value="<?= (int)($editProduct['id'] ?? 0) ?>">
                <div class="mb-2"><input class="form-control" placeholder="Name" name="name" value="<?= h($editProduct['name'] ?? '') ?>" required></div>
                <div class="mb-2"><input class="form-control" type="number" step="0.01" name="price" value="<?= h((string)($editProduct['price'] ?? '')) ?>" placeholder="Price" required></div>
                <div class="mb-2"><input class="form-control" type="number" name="stock" value="<?= h((string)($editProduct['stock'] ?? '')) ?>" placeholder="Stock" required></div>
                <div class="mb-2">
                    <select name="category_id" class="form-select" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (int)($editProduct['category_id'] ?? 0)===(int)$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
                <button class="btn btn-primary w-100">Save Product</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-8">
        <h5>Your Products & Inventory</h5>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Action</th></tr>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php if($p['image']): ?><img src="/uploads/<?= h($p['image']) ?>" width="45" alt=""><?php endif; ?></td>
                    <td><?= h($p['name']) ?></td>
                    <td><?= h($p['category_name'] ?? 'N/A') ?></td>
                    <td>$<?= number_format((float)$p['price'],2) ?></td>
                    <td><?= (int)$p['stock'] ?></td>
                    <td class="d-flex gap-1">
                        <a href="/seller/dashboard.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" data-confirm="Delete product?">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<h5 class="mt-4">Orders for Your Products</h5>
<div class="table-responsive">
    <table class="table table-sm table-striped">
        <tr><th>Order #</th><th>Customer</th><th>Product</th><th>Qty</th><th>Line Price</th><th>Status</th><th>Date</th></tr>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= (int)$o['id'] ?></td><td><?= h($o['customer']) ?></td><td><?= h($o['product_name']) ?></td><td><?= (int)$o['quantity'] ?></td>
                <td>$<?= number_format((float)$o['price'], 2) ?></td><td><?= h($o['status']) ?></td><td><?= h($o['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php render_footer(); ?>
