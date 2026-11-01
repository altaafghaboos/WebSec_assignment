<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

// Helper function to validate target source constraint (chk_payment_source)
function validatePaymentSource($target_type, $appointment_id, $order_id) {
    if ($target_type === 'appointment') {
        return [$appointment_id ?: null, null];
    } elseif ($target_type === 'order') {
        return [null, $order_id ?: null];
    }
    return [null, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    $customer_id           = (int)$_POST['customer_id'];
    $target_type           = $_POST['target_type'];
    $raw_apt_id            = (int)($_POST['appointment_id'] ?? 0);
    $raw_order_id          = (int)($_POST['order_id'] ?? 0);
    $amount                = (float)$_POST['amount'];
    $payment_method        = $_POST['payment_method'];
    $payment_status        = $_POST['payment_status'];
    $transaction_reference = trim($_POST['transaction_reference']);
    $payment_date          = $_POST['payment_date'];

    list($appointment_id, $order_id) = validatePaymentSource($target_type, $raw_apt_id, $raw_order_id);

    // Enforce chk_payment_source constraint
    if (($appointment_id === null && $order_id === null) || ($appointment_id !== null && $order_id !== null)) {
        $error = "Validation Error: Payment must be linked to EITHER an Appointment OR a Product Order (not both).";
    } elseif ($customer_id <= 0 || $amount <= 0) {
        $error = "Please select a customer and provide a valid positive payment amount.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payments 
                (customer_id, appointment_id, order_id, amount, payment_method, payment_status, transaction_reference, payment_date)
                VALUES (:customer_id, :appointment_id, :order_id, :amount, :payment_method, :payment_status, :transaction_reference, :payment_date)
            ");
            $stmt->execute([
                'customer_id'           => $customer_id,
                'appointment_id'        => $appointment_id,
                'order_id'              => $order_id,
                'amount'                => $amount,
                'payment_method'        => $payment_method,
                'payment_status'        => $payment_status,
                'transaction_reference' => $transaction_reference ?: null,
                'payment_date'          => $payment_date ?: null
            ]);
            $message = "Payment record added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique key constraint on transaction_reference
                $error = "Transaction reference must be unique.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id            = (int)$_POST['payment_id'];
    $customer_id           = (int)$_POST['customer_id'];
    $target_type           = $_POST['target_type'];
    $raw_apt_id            = (int)($_POST['appointment_id'] ?? 0);
    $raw_order_id          = (int)($_POST['order_id'] ?? 0);
    $amount                = (float)$_POST['amount'];
    $payment_method        = $_POST['payment_method'];
    $payment_status        = $_POST['payment_status'];
    $transaction_reference = trim($_POST['transaction_reference']);
    $payment_date          = $_POST['payment_date'];

    list($appointment_id, $order_id) = validatePaymentSource($target_type, $raw_apt_id, $raw_order_id);

    if (($appointment_id === null && $order_id === null) || ($appointment_id !== null && $order_id !== null)) {
        $error = "Validation Error: Payment must be linked to EITHER an Appointment OR a Product Order.";
    } elseif ($payment_id <= 0 || $customer_id <= 0 || $amount <= 0) {
        $error = "Required fields cannot be empty or non-positive.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET customer_id = :customer_id, appointment_id = :appointment_id, order_id = :order_id, 
                    amount = :amount, payment_method = :payment_method, payment_status = :payment_status, 
                    transaction_reference = :transaction_reference, payment_date = :payment_date 
                WHERE payment_id = :payment_id
            ");
            $stmt->execute([
                'customer_id'           => $customer_id,
                'appointment_id'        => $appointment_id,
                'order_id'              => $order_id,
                'amount'                => $amount,
                'payment_method'        => $payment_method,
                'payment_status'        => $payment_status,
                'transaction_reference' => $transaction_reference ?: null,
                'payment_date'          => $payment_date ?: null,
                'payment_id'            => $payment_id
            ]);
            $message = "Payment details updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Transaction reference must be unique.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM payments WHERE payment_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Payment record deleted successfully!";
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

$sql = "
    SELECT 
        p.payment_id,
        p.customer_id,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        p.appointment_id,
        p.order_id,
        p.amount,
        p.payment_method,
        p.payment_status,
        p.transaction_reference,
        p.payment_date
    FROM payments p
    JOIN customers c ON c.customer_id = p.customer_id
    ORDER BY p.payment_id DESC
";
$payments = $pdo->query($sql)->fetchAll();

// Fetch drop-down selections for Customers, Appointments, and Product Orders
$all_customers = $pdo->query("SELECT customer_id, first_name, last_name FROM customers ORDER BY first_name ASC")->fetchAll();
$all_appointments = $pdo->query("
    SELECT a.appointment_id, a.appointment_date, CONCAT(c.first_name, ' ', c.last_name) AS customer_name 
    FROM appointments a 
    JOIN customers c ON c.customer_id = a.customer_id 
    ORDER BY a.appointment_id DESC
")->fetchAll();
$all_orders = $pdo->query("
    SELECT o.order_id, o.total_amount, CONCAT(c.first_name, ' ', c.last_name) AS customer_name 
    FROM customer_orders o 
    JOIN customers c ON c.customer_id = o.customer_id 
    ORDER BY o.order_id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="number"], input[type="datetime-local"], select {
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
        .bg-Pending { background-color: #ffc107; color: black; }
        .bg-Paid { background-color: #28a745; }
        .bg-Failed { background-color: #dc3545; }
        .bg-Refunded { background-color: #17a2b8; }
    </style>
    <script>
        // Toggle input fields depending on whether payment applies to an Appointment or Order
        function toggleSourceFields(prefix) {
            const targetType = document.getElementById(prefix + '_target_type').value;
            const aptGroup = document.getElementById(prefix + '_apt_group');
            const orderGroup = document.getElementById(prefix + '_order_group');

            if (targetType === 'appointment') {
                aptGroup.style.display = 'block';
                orderGroup.style.display = 'none';
            } else if (targetType === 'order') {
                aptGroup.style.display = 'none';
                orderGroup.style.display = 'block';
            }
        }
    </script>
</head>
<body>

    <h2>Payments Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW PAYMENT FORM -->
    <div class="form-card">
        <h3>Record New Payment</h3>
        <form method="POST" action="admin_payments.php">
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
                    <label>Payment Target *</label>
                    <select name="target_type" id="add_target_type" onchange="toggleSourceFields('add')" required>
                        <option value="appointment">Appointment</option>
                        <option value="order">Product Order</option>
                    </select>
                </div>
                <div class="form-group" id="add_apt_group">
                    <label>Appointment</label>
                    <select name="appointment_id">
                        <option value="">-- Select Appointment --</option>
                        <?php foreach ($all_appointments as $apt): ?>
                            <option value="<?= $apt['appointment_id'] ?>">
                                Apt #<?= $apt['appointment_id'] ?> - <?= htmlspecialchars($apt['customer_name']) ?> (<?= $apt['appointment_date'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="add_order_group" style="display:none;">
                    <label>Product Order</label>
                    <select name="order_id">
                        <option value="">-- Select Order --</option>
                        <?php foreach ($all_orders as $ord): ?>
                            <option value="<?= $ord['order_id'] ?>">
                                Order #<?= $ord['order_id'] ?> - <?= htmlspecialchars($ord['customer_name']) ?> (MUR <?= number_format($ord['total_amount'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Amount (MUR) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Payment">Mobile Payment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="payment_status">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Failed">Failed</option>
                        <option value="Refunded">Refunded</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Transaction Reference</label>
                    <input type="text" name="transaction_reference" placeholder="e.g. APT-2026-0001">
                </div>
                <div class="form-group">
                    <label>Payment Date & Time</label>
                    <input type="datetime-local" name="payment_date">
                </div>
            </div>
            <button type="submit" name="create_payment" class="btn btn-add">Record Payment</button>
        </form>
    </div>

    <!-- PAYMENTS TABLE -->
    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Customer</th>
                <th>Source Type</th>
                <th>Linked ID</th>
                <th>Amount (MUR)</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Payment Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <?php if ($edit_id == $p['payment_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_payments.php">
                            <td>#<?= $p['payment_id'] ?></td>
                            <td>
                                <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                                <select name="customer_id" required>
                                    <?php foreach ($all_customers as $cust): ?>
                                        <option value="<?= $cust['customer_id'] ?>" <?= $cust['customer_id'] == $p['customer_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="target_type" id="edit_target_type" onchange="toggleSourceFields('edit')" required>
                                    <option value="appointment" <?= $p['appointment_id'] ? 'selected' : '' ?>>Appointment</option>
                                    <option value="order" <?= $p['order_id'] ? 'selected' : '' ?>>Order</option>
                                </select>
                            </td>
                            <td>
                                <div id="edit_apt_group" style="display:<?= $p['appointment_id'] ? 'block' : 'none' ?>;">
                                    <select name="appointment_id">
                                        <?php foreach ($all_appointments as $apt): ?>
                                            <option value="<?= $apt['appointment_id'] ?>" <?= $apt['appointment_id'] == $p['appointment_id'] ? 'selected' : '' ?>>
                                                Apt #<?= $apt['appointment_id'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="edit_order_group" style="display:<?= $p['order_id'] ? 'block' : 'none' ?>;">
                                    <select name="order_id">
                                        <?php foreach ($all_orders as $ord): ?>
                                            <option value="<?= $ord['order_id'] ?>" <?= $ord['order_id'] == $p['order_id'] ? 'selected' : '' ?>>
                                                Order #<?= $ord['order_id'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" name="amount" value="<?= $p['amount'] ?>" required style="width:80px;">
                            </td>
                            <td>
                                <select name="payment_method">
                                    <?php foreach (['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Mobile Payment'] as $m): ?>
                                        <option value="<?= $m ?>" <?= $p['payment_method'] === $m ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="transaction_reference" value="<?= htmlspecialchars($p['transaction_reference'] ?? '') ?>" style="width:110px;">
                            </td>
                            <td>
                                <input type="datetime-local" name="payment_date" value="<?= $p['payment_date'] ? date('Y-m-d\TH:i', strtotime($p['payment_date'])) : '' ?>">
                            </td>
                            <td>
                                <select name="payment_status">
                                    <?php foreach (['Pending', 'Paid', 'Failed', 'Refunded'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $p['payment_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_payment" class="btn btn-save">Save</button>
                                <a href="admin_payments.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td>#<?= $p['payment_id'] ?></td>
                        <td><?= htmlspecialchars($p['customer_name']) ?></td>
                        <td><?= $p['appointment_id'] ? 'Appointment' : 'Product Order' ?></td>
                        <td><?= $p['appointment_id'] ? 'Apt #' . $p['appointment_id'] : 'Order #' . $p['order_id'] ?></td>
                        <td><strong>MUR <?= number_format($p['amount'], 2) ?></strong></td>
                        <td><?= htmlspecialchars($p['payment_method']) ?></td>
                        <td><?= htmlspecialchars($p['transaction_reference'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['payment_date'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-<?= htmlspecialchars($p['payment_status']) ?>">
                                <?= htmlspecialchars($p['payment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_payments.php?edit=<?= $p['payment_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_payments.php?delete=<?= $p['payment_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this payment record?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
