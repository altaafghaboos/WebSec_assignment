<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order_item'])) {
    $order_id   = (int)$_POST['order_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];
    $unit_price = (float)$_POST['unit_price'];

    if ($order_id > 0 && $product_id > 0 && $quantity > 0 && $unit_price >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (:order_id, :product_id, :quantity, :unit_price)
            ");
            $stmt->execute([
                'order_id'   => $order_id,
                'product_id' => $product_id,
                'quantity'   => $quantity,
                'unit_price' => $unit_price
            ]);
            $message = "Item added to order successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique key constraint uq_order_product
                $error = "This product has already been added to the selected order.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please enter valid, positive values for quantity and non-negative unit price.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_item'])) {
    $order_item_id = (int)$_POST['order_item_id'];
    $order_id      = (int)$_POST['order_id'];
    $product_id    = (int)$_POST['product_id'];
    $quantity      = (int)$_POST['quantity'];
    $unit_price    = (float)$_POST['unit_price'];

    if ($order_item_id > 0 && $order_id > 0 && $product_id > 0 && $quantity > 0 && $unit_price >= 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE order_items 
                SET order_id = :order_id, product_id = :product_id, 
                    quantity = :quantity, unit_price = :unit_price 
                WHERE order_item_id = :order_item_id
            ");
            $stmt->execute([
                'order_id'      => $order_id,
                'product_id'    => $product_id,
                'quantity'      => $quantity,
                'unit_price'    => $unit_price,
                'order_item_id' => $order_item_id
            ]);
            $message = "Order item updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "This product already exists in the selected order.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Required fields cannot be empty or invalid.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_item_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Order item removed successfully!";
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

$sql = "
    SELECT 
        oi.order_item_id,
        oi.order_id,
        oi.product_id,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        p.product_name,
        oi.quantity,
        oi.unit_price,
        oi.line_total
    FROM order_items oi
    JOIN customer_orders o ON o.order_id = oi.order_id
    JOIN customers c ON c.customer_id = o.customer_id
    JOIN products p ON p.product_id = oi.product_id
    ORDER BY oi.order_id DESC, oi.order_item_id ASC
";
$order_items = $pdo->query($sql)->fetchAll();

// Fetch drop-down data for Orders and Products
$all_orders = $pdo->query("
    SELECT o.order_id, o.order_date, CONCAT(c.first_name, ' ', c.last_name) AS customer_name 
    FROM customer_orders o 
    JOIN customers c ON c.customer_id = o.customer_id 
    ORDER BY o.order_id DESC
")->fetchAll();

$all_products = $pdo->query("SELECT product_id, product_name, unit_price FROM products WHERE product_status = 'Available' ORDER BY product_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Items Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="number"], select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
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
    </style>
    <script>
        // Auto-fill unit price input based on product selection
        function setUnitPrice(selectElem, targetInputId) {
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price !== null) {
                document.getElementById(targetInputId).value = price;
            }
        }
    </script>
</head>
<body>

    <h2>Order Items Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW ORDER ITEM FORM -->
    <div class="form-card">
        <h3>Add Item to Order</h3>
        <form method="POST" action="admin_order_items.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Order *</label>
                    <select name="order_id" required>
                        <option value="">-- Select Order --</option>
                        <?php foreach ($all_orders as $ord): ?>
                            <option value="<?= $ord['order_id'] ?>">
                                Order #<?= $ord['order_id'] ?> - <?= htmlspecialchars($ord['customer_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product *</label>
                    <select name="product_id" onchange="setUnitPrice(this, 'add_price_input')" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($all_products as $prd): ?>
                            <option value="<?= $prd['product_id'] ?>" data-price="<?= $prd['unit_price'] ?>">
                                <?= htmlspecialchars($prd['product_name']) ?> (MUR <?= number_format($prd['unit_price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" min="1" name="quantity" value="1" required>
                </div>
                <div class="form-group">
                    <label>Unit Price (MUR) *</label>
                    <input type="number" step="0.01" min="0" name="unit_price" id="add_price_input" placeholder="0.00" required>
                </div>
            </div>
            <button type="submit" name="create_order_item" class="btn btn-add">Add Item to Order</button>
        </form>
    </div>

    <!-- ORDER ITEMS TABLE -->
    <table>
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price (MUR)</th>
                <th>Line Total (MUR)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $item): ?>
                <tr>
                    <?php if ($edit_id == $item['order_item_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_order_items.php">
                            <td><?= $item['order_item_id'] ?></td>
                            <td>
                                <input type="hidden" name="order_item_id" value="<?= $item['order_item_id'] ?>">
                                <select name="order_id" required>
                                    <?php foreach ($all_orders as $ord): ?>
                                        <option value="<?= $ord['order_id'] ?>" <?= $ord['order_id'] == $item['order_id'] ? 'selected' : '' ?>>
                                            Order #<?= $ord['order_id'] ?> - <?= htmlspecialchars($ord['customer_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($item['customer_name']) ?></td>
                            <td>
                                <select name="product_id" onchange="setUnitPrice(this, 'edit_price_input')" required>
                                    <?php foreach ($all_products as $prd): ?>
                                        <option value="<?= $prd['product_id'] ?>" data-price="<?= $prd['unit_price'] ?>" <?= $prd['product_id'] == $item['product_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prd['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" min="1" name="quantity" value="<?= $item['quantity'] ?>" required style="width:60px;">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="unit_price" id="edit_price_input" value="<?= $item['unit_price'] ?>" required style="width:90px;">
                            </td>
                            <td><strong><?= number_format($item['line_total'], 2) ?></strong></td>
                            <td>
                                <button type="submit" name="update_order_item" class="btn btn-save">Save</button>
                                <a href="admin_order_items.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= $item['order_item_id'] ?></td>
                        <td>#<?= $item['order_id'] ?></td>
                        <td><?= htmlspecialchars($item['customer_name']) ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['unit_price'], 2) ?></td>
                        <td><strong><?= number_format($item['line_total'], 2) ?></strong></td>
                        <td>
                            <a href="admin_order_items.php?edit=<?= $item['order_item_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_order_items.php?delete=<?= $item['order_item_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Remove this item from the order?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
