<?php
require 'db.php';

$message = '';
$error = '';
$edit_appointment_id = $_GET['edit_apt'] ?? null;
$edit_service_id     = $_GET['edit_srv'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment_service'])) {
    $appointment_id   = (int)$_POST['appointment_id'];
    $service_id       = (int)$_POST['service_id'];
    $price_at_booking = (float)$_POST['price_at_booking'];

    if ($appointment_id > 0 && $service_id > 0 && $price_at_booking >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO appointment_services (appointment_id, service_id, price_at_booking)
                VALUES (:appointment_id, :service_id, :price_at_booking)
            ");
            $stmt->execute([
                'appointment_id'   => $appointment_id,
                'service_id'       => $service_id,
                'price_at_booking' => $price_at_booking
            ]);
            $message = "Service added to appointment successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Primary key / duplicate constraint error
                $error = "This service is already added to the selected appointment.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please select an appointment, service, and enter a valid non-negative price.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment_service'])) {
    $old_appointment_id = (int)$_POST['old_appointment_id'];
    $old_service_id     = (int)$_POST['old_service_id'];
    $new_appointment_id = (int)$_POST['appointment_id'];
    $new_service_id     = (int)$_POST['service_id'];
    $price_at_booking   = (float)$_POST['price_at_booking'];

    if ($old_appointment_id > 0 && $old_service_id > 0 && $new_appointment_id > 0 && $new_service_id > 0 && $price_at_booking >= 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE appointment_services 
                SET appointment_id = :new_appointment_id, 
                    service_id = :new_service_id, 
                    price_at_booking = :price_at_booking 
                WHERE appointment_id = :old_appointment_id AND service_id = :old_service_id
            ");
            $stmt->execute([
                'new_appointment_id' => $new_appointment_id,
                'new_service_id'     => $new_service_id,
                'price_at_booking'   => $price_at_booking,
                'old_appointment_id' => $old_appointment_id,
                'old_service_id'     => $old_service_id
            ]);
            $message = "Appointment service updated successfully!";
            $edit_appointment_id = null;
            $edit_service_id     = null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "That service is already assigned to the target appointment.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Invalid parameters or price provided.";
    }
}

if (isset($_GET['delete_apt']) && isset($_GET['delete_srv'])) {
    $del_apt_id = (int)$_GET['delete_apt'];
    $del_srv_id = (int)$_GET['delete_srv'];

    try {
        $stmt = $pdo->prepare("DELETE FROM appointment_services WHERE appointment_id = :apt_id AND service_id = :srv_id");
        $stmt->execute([
            'apt_id' => $del_apt_id,
            'srv_id' => $del_srv_id
        ]);
        $message = "Service removed from appointment successfully!";
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

$sql = "
    SELECT 
        aps.appointment_id,
        aps.service_id,
        aps.price_at_booking,
        a.appointment_date,
        a.start_time,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        sv.service_name,
        sv.price AS catalog_price
    FROM appointment_services aps
    JOIN appointments a ON a.appointment_id = aps.appointment_id
    JOIN customers c ON c.customer_id = a.customer_id
    JOIN services sv ON sv.service_id = aps.service_id
    ORDER BY a.appointment_date DESC, aps.appointment_id DESC
";
$app_services = $pdo->query($sql)->fetchAll();

// Fetch drop-down data for Appointments and Services
$all_appointments = $pdo->query("
    SELECT a.appointment_id, a.appointment_date, a.start_time, CONCAT(c.first_name, ' ', c.last_name) AS customer_name 
    FROM appointments a 
    JOIN customers c ON c.customer_id = a.customer_id 
    ORDER BY a.appointment_date DESC
")->fetchAll();

$all_services = $pdo->query("SELECT service_id, service_name, price FROM services WHERE service_status = 'Available' ORDER BY service_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Services - Salon System</title>
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
        // Auto-fill price input with the selected catalog service price
        function updatePrice(selectElem, targetInputId) {
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price !== null) {
                document.getElementById(targetInputId).value = price;
            }
        }
    </script>
</head>
<body>

    <h2>Appointment Services Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD SERVICE TO APPOINTMENT FORM -->
    <div class="form-card">
        <h3>Add Service to Appointment</h3>
        <form method="POST" action="admin_appointment_services.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Appointment *</label>
                    <select name="appointment_id" required>
                        <option value="">-- Select Appointment --</option>
                        <?php foreach ($all_appointments as $apt): ?>
                            <option value="<?= $apt['appointment_id'] ?>">
                                #<?= $apt['appointment_id'] ?> - <?= htmlspecialchars($apt['customer_name']) ?> (<?= $apt['appointment_date'] ?> at <?= substr($apt['start_time'], 0, 5) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service *</label>
                    <select name="service_id" onchange="updatePrice(this, 'add_price_input')" required>
                        <option value="">-- Select Service --</option>
                        <?php foreach ($all_services as $sv): ?>
                            <option value="<?= $sv['service_id'] ?>" data-price="<?= $sv['price'] ?>">
                                <?= htmlspecialchars($sv['service_name']) ?> (MUR <?= number_format($sv['price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price at Booking (MUR) *</label>
                    <input type="number" step="0.01" min="0" name="price_at_booking" id="add_price_input" placeholder="0.00" required>
                </div>
            </div>
            <button type="submit" name="add_appointment_service" class="btn btn-add">Add Service to Appointment</button>
        </form>
    </div>

    <!-- APPOINTMENT SERVICES TABLE -->
    <table>
        <thead>
            <tr>
                <th>Apt ID</th>
                <th>Customer</th>
                <th>Apt Date & Time</th>
                <th>Selected Service</th>
                <th>Price at Booking (MUR)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($app_services as $row): ?>
                <tr>
                    <?php if ($edit_appointment_id == $row['appointment_id'] && $edit_service_id == $row['service_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_appointment_services.php">
                            <input type="hidden" name="old_appointment_id" value="<?= $row['appointment_id'] ?>">
                            <input type="hidden" name="old_service_id" value="<?= $row['service_id'] ?>">
                            <td><?= $row['appointment_id'] ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td>
                                <select name="appointment_id" required>
                                    <?php foreach ($all_appointments as $apt): ?>
                                        <option value="<?= $apt['appointment_id'] ?>" <?= $apt['appointment_id'] == $row['appointment_id'] ? 'selected' : '' ?>>
                                            #<?= $apt['appointment_id'] ?> - <?= htmlspecialchars($apt['customer_name']) ?> (<?= $apt['appointment_date'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="service_id" onchange="updatePrice(this, 'edit_price_input')" required>
                                    <?php foreach ($all_services as $sv): ?>
                                        <option value="<?= $sv['service_id'] ?>" data-price="<?= $sv['price'] ?>" <?= $sv['service_id'] == $row['service_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="price_at_booking" id="edit_price_input" value="<?= $row['price_at_booking'] ?>" required>
                            </td>
                            <td>
                                <button type="submit" name="update_appointment_service" class="btn btn-save">Save</button>
                                <a href="admin_appointment_services.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td>#<?= $row['appointment_id'] ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['appointment_date']) ?> at <?= htmlspecialchars(substr($row['start_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td><?= number_format($row['price_at_booking'], 2) ?></td>
                        <td>
                            <a href="admin_appointment_services.php?edit_apt=<?= $row['appointment_id'] ?>&edit_srv=<?= $row['service_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_appointment_services.php?delete_apt=<?= $row['appointment_id'] ?>&delete_srv=<?= $row['service_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Remove this service from the appointment?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
