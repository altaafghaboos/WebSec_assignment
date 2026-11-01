<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {
    $product_name   = trim($_POST['product_name']);
    $description    = trim($_POST['description']);
    $category       = trim($_POST['category']);
    $unit_price     = (float)$_POST['unit_price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $reorder_level  = (int)$_POST['reorder_level'];
    $product_status = $_POST['product_status'];

    if (!empty($product_name) && !empty($category) && $unit_price >= 0 && $stock_quantity >= 0 && $reorder_level >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (product_name, description, category, unit_price, stock_quantity, reorder_level, product_status)
                VALUES (:product_name, :description, :category, :unit_price, :stock_quantity, :reorder_level, :product_status)
            ");
            $stmt->execute([
                'product_name'   => $product_name,
                'description'    => $description ?: null,
                'category'       => $category,
                'unit_price'     => $unit_price,
                'stock_quantity' => $stock_quantity,
                'reorder_level'  => $reorder_level,
                'product_status' => $product_status
            ]);
            $message = "Product added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A product with this name already exists.";
            } else {
                $error = "Error adding product: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields with valid values.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id     = (int)$_POST['product_id'];
    $product_name   = trim($_POST['product_name']);
    $description    = trim($_POST['description']);
    $category       = trim($_POST['category']);
    $unit_price     = (float)$_POST['unit_price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $reorder_level  = (int)$_POST['reorder_level'];
    $product_status = $_POST['product_status'];

    if ($product_id > 0 && !empty($product_name) && !empty($category) && $unit_price >= 0 && $stock_quantity >= 0 && $reorder_level >= 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = :product_name, description = :description, category = :category, 
                    unit_price = :unit_price, stock_quantity = :stock_quantity, 
                    reorder_level = :reorder_level, product_status = :product_status 
                WHERE product_id = :product_id
            ");
            $stmt->execute([
                'product_name'   => $product_name,
                'description'    => $description ?: null,
                'category'       => $category,
                'unit_price'     => $unit_price,
                'stock_quantity' => $stock_quantity,
                'reorder_level'  => $reorder_level,
                'product_status' => $product_status,
                'product_id'     => $product_id
            ]);
            $message = "Product details updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A product with this name already exists.";
            } else {
                $error = "Error updating product: " . $e->getMessage();
            }
        }
    } else {
        $error = "Required fields cannot be empty or negative.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Product deleted successfully!";
    } catch (PDOException $e) {
        $error = "Cannot delete product (it is linked to existing customer orders).";
    }
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY product_id ASC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="number"], select {
            padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;
        }
        table { width: 100%; border-collapse: collapse; background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #343a40; color: white; }
        tr:hover { background-color: #f1f1f1; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; }
        .btn-add { background-color: #28a745; color: white; margin-top: 10px; }
        .btn-edit { background-color: #ffc107; color: black; }
        .btn-save { background-color: #007bff; color: white; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-cancel { background-color: #6c757d; color: white; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; color: white; }
        .bg-Available { background-color: #28a745; }
        .bg-OutOfStock { background-color: #ffc107; color: black; }
        .bg-Discontinued { background-color: #dc3545; }
        .stock-warning { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Product Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW PRODUCT FORM -->
    <div class="form-card">
        <h3>Add New Product</h3>
        <form method="POST" action="admin_products.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="product_name" placeholder="e.g. Gentle Face Cleanser" required>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" placeholder="e.g. Skincare" required>
                </div>
                <div class="form-group">
                    <label>Unit Price (MUR) *</label>
                    <input type="number" step="0.01" min="0" name="unit_price" placeholder="500.00" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" min="0" name="stock_quantity" value="0" required>
                </div>
                <div class="form-group">
                    <label>Reorder Level *</label>
                    <input type="number" min="0" name="reorder_level" value="5" required>
                </div>
                <div class="form-group">
                    <label>Product Status *</label>
                    <select name="product_status">
                        <option value="Available">Available</option>
                        <option value="Out of Stock">Out of Stock</option>
                        <option value="Discontinued">Discontinued</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Brief product description...">
                </div>
            </div>
            <button type="submit" name="create_product" class="btn btn-add">Add Product</button>
        </form>
    </div>

    <!-- PRODUCTS TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Description</th>
                <th>Price (MUR)</th>
                <th>Stock</th>
                <th>Reorder Level</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <?php if ($edit_id == $p['product_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_products.php">
                            <td><?= $p['product_id'] ?></td>
                            <td>
                                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                <input type="text" name="product_name" value="<?= htmlspecialchars($p['product_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="category" value="<?= htmlspecialchars($p['category']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description" value="<?= htmlspecialchars($p['description'] ?? '') ?>">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="unit_price" value="<?= $p['unit_price'] ?>" required>
                            </td>
                            <td>
                                <input type="number" min="0" name="stock_quantity" value="<?= $p['stock_quantity'] ?>" required>
                            </td>
                            <td>
                                <input type="number" min="0" name="reorder_level" value="<?= $p['reorder_level'] ?>" required>
                            </td>
                            <td>
                                <select name="product_status">
                                    <option value="Available" <?= $p['product_status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                    <option value="Out of Stock" <?= $p['product_status'] === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                                    <option value="Discontinued" <?= $p['product_status'] === 'Discontinued' ? 'selected' : '' ?>>Discontinued</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_product" class="btn btn-save">Save</button>
                                <a href="admin_products.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= $p['product_id'] ?></td>
                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td><?= htmlspecialchars($p['description'] ?? '-') ?></td>
                        <td><?= number_format($p['unit_price'], 2) ?></td>
                        <td class="<?= ($p['stock_quantity'] <= $p['reorder_level']) ? 'stock-warning' : '' ?>">
                            <?= $p['stock_quantity'] ?>
                            <?= ($p['stock_quantity'] <= $p['reorder_level']) ? ' (Low)' : '' ?>
                        </td>
                        <td><?= $p['reorder_level'] ?></td>
                        <td>
                            <span class="badge bg-<?= str_replace(' ', '', $p['product_status']) ?>">
                                <?= htmlspecialchars($p['product_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_products.php?edit=<?= $p['product_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_products.php?delete=<?= $p['product_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
