<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $customer_id       = (int)$_POST['customer_id'];
    $order_status      = $_POST['order_status'];
    $collection_method = $_POST['collection_method'];
    $delivery_address  = trim($_POST['delivery_address']);
    $subtotal          = (float)$_POST['subtotal'];
    $delivery_fee      = (float)$_POST['delivery_fee'];

    // Enforce check constraint: total_amount = subtotal + delivery_fee
    $total_amount = $subtotal + $delivery_fee;

    if ($customer_id > 0 && $subtotal >= 0 && $delivery_fee >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO customer_orders 
                (customer_id, order_status, collection_method, delivery_address, subtotal, delivery_fee, total_amount)
                VALUES (:customer_id, :order_status, :collection_method, :delivery_address, :subtotal, :delivery_fee, :total_amount)
            ");
            $stmt->execute([
                'customer_id'       => $customer_id,
                'order_status'      => $order_status,
                'collection_method' => $collection_method,
                'delivery_address'  => $delivery_address ?: null,
                'subtotal'          => $subtotal,
                'delivery_fee'      => $delivery_fee,
                'total_amount'      => $total_amount
            ]);
            $message = "Customer order created successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please select a valid customer and ensure amounts are non-negative.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id          = (int)$_POST['order_id'];
    $customer_id       = (int)$_POST['customer_id'];
    $order_status      = $_POST['order_status'];
    $collection_method = $_POST['collection_method'];
    $delivery_address  = trim($_POST['delivery_address']);
    $subtotal          = (float)$_POST['subtotal'];
    $delivery_fee      = (float)$_POST['delivery_fee'];

    $total_amount = $subtotal + $delivery_fee;

    if ($order_id > 0 && $customer_id > 0 && $subtotal >= 0 && $delivery_fee >= 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE customer_orders 
                SET customer_id = :customer_id, order_status = :order_status, 
                    collection_method = :collection_method, delivery_address = :delivery_address, 
                    subtotal = :subtotal, delivery_fee = :delivery_fee, total_amount = :total_amount 
                WHERE order_id = :order_id
            ");
            $stmt->execute([
                'customer_id'       => $customer_id,
                'order_status'      => $order_status,
                'collection_method' => $collection_method,
                'delivery_address'  => $delivery_address ?: null,
                'subtotal'          => $subtotal,
                'delivery_fee'      => $delivery_fee,
                'total_amount'      => $total_amount,
                'order_id'          => $order_id
            ]);
            $message = "Order updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Required fields cannot be empty or negative.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM customer_orders WHERE order_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Order deleted successfully!";
    } catch (PDOException $e) {
        $error = "Cannot delete order (it is linked to order items or payment records).";
    }
}

$sql = "
    SELECT 
        o.order_id,
        o.customer_id,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        o.order_date,
        o.order_status,
        o.collection_method,
        o.delivery_address,
        o.subtotal,
        o.delivery_fee,
        o.total_amount
    FROM customer_orders o
    JOIN customers c ON c.customer_id = o.customer_id
    ORDER BY o.order_date DESC
";
$orders = $pdo->query($sql)->fetchAll();

// Fetch customer options for drop-down menus
$all_customers = $pdo->query("SELECT customer_id, first_name, last_name FROM customers WHERE account_status = 'Active' ORDER BY first_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="number"], select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
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
        .bg-Pending { background-color: #ffc107; color: black; }
        .bg-Confirmed { background-color: #007bff; }
        .bg-Ready { background-color: #17a2b8; }
        .bg-Collected { background-color: #28a745; }
        .bg-Cancelled { background-color: #dc3545; }
    </style>
    <script>
        // Automatic calculation of Total Amount = Subtotal + Delivery Fee
        function calculateTotal(prefix) {
            const subtotal = parseFloat(document.getElementById(prefix + '_subtotal').value) || 0;
            const fee = parseFloat(document.getElementById(prefix + '_fee').value) || 0;
            document.getElementById(prefix + '_total').value = (subtotal + fee).toFixed(2);
        }
    </script>
</head>
<body>

    <h2>Customer Orders Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW ORDER FORM -->
    <div class="form-card">
        <h3>Create New Order</h3>
        <form method="POST" action="admin_customer_orders.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Customer *</label>
                    <select name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($all_customers as $cust): ?>
                            <option value="<?= $cust['customer_id'] ?>">
                                <?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order Status *</label>
                    <select name="order_status">
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Ready">Ready</option>
                        <option value="Collected">Collected</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Collection Method *</label>
                    <select name="collection_method">
                        <option value="Salon Pickup">Salon Pickup</option>
                        <option value="Delivery">Delivery</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Subtotal (MUR) *</label>
                    <input type="number" step="0.01" min="0" name="subtotal" id="add_subtotal" oninput="calculateTotal('add')" value="0.00" required>
                </div>
                <div class="form-group">
                    <label>Delivery Fee (MUR) *</label>
                    <input type="number" step="0.01" min="0" name="delivery_fee" id="add_fee" oninput="calculateTotal('add')" value="0.00" required>
                </div>
                <div class="form-group">
                    <label>Total Amount (Calculated)</label>
                    <input type="number" step="0.01" id="add_total" value="0.00" disabled style="background-color:#e9ecef;">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Delivery Address</label>
                    <input type="text" name="delivery_address" placeholder="Required if method is Delivery...">
                </div>
            </div>
            <button type="submit" name="create_order" class="btn btn-add">Create Order</button>
        </form>
    </div>

    <!-- ORDERS TABLE -->
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Order Date</th>
                <th>Method</th>
                <th>Delivery Address</th>
                <th>Subtotal</th>
                <th>Fee</th>
                <th>Total (MUR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <?php if ($edit_id == $o['order_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_customer_orders.php">
                            <td>#<?= $o['order_id'] ?></td>
                            <td>
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                <select name="customer_id" required>
                                    <?php foreach ($all_customers as $cust): ?>
                                        <option value="<?= $cust['customer_id'] ?>" <?= $cust['customer_id'] == $o['customer_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= $o['order_date'] ?></td>
                            <td>
                                <select name="collection_method">
                                    <option value="Salon Pickup" <?= $o['collection_method'] === 'Salon Pickup' ? 'selected' : '' ?>>Salon Pickup</option>
                                    <option value="Delivery" <?= $o['collection_method'] === 'Delivery' ? 'selected' : '' ?>>Delivery</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="delivery_address" value="<?= htmlspecialchars($o['delivery_address'] ?? '') ?>">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="subtotal" id="edit_subtotal" oninput="calculateTotal('edit')" value="<?= $o['subtotal'] ?>" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="delivery_fee" id="edit_fee" oninput="calculateTotal('edit')" value="<?= $o['delivery_fee'] ?>" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" id="edit_total" value="<?= $o['total_amount'] ?>" disabled style="background-color:#e9ecef; width:70px;">
                            </td>
                            <td>
                                <select name="order_status">
                                    <?php 
                                    $statuses = ['Pending', 'Confirmed', 'Ready', 'Collected', 'Cancelled'];
                                    foreach ($statuses as $st_val): ?>
                                        <option value="<?= $st_val ?>" <?= $o['order_status'] === $st_val ? 'selected' : '' ?>><?= $st_val ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_order" class="btn btn-save">Save</button>
                                <a href="admin_customer_orders.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td>#<?= $o['order_id'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['order_date']) ?></td>
                        <td><?= htmlspecialchars($o['collection_method']) ?></td>
                        <td><?= htmlspecialchars($o['delivery_address'] ?? '-') ?></td>
                        <td><?= number_format($o['subtotal'], 2) ?></td>
                        <td><?= number_format($o['delivery_fee'], 2) ?></td>
                        <td><strong><?= number_format($o['total_amount'], 2) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= str_replace(' ', '', $o['order_status']) ?>">
                                <?= htmlspecialchars($o['order_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_customer_orders.php?edit=<?= $o['order_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_customer_orders.php?delete=<?= $o['order_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this order?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
