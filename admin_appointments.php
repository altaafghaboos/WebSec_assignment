<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appointment'])) {
    $customer_id        = (int)$_POST['customer_id'];
    $staff_id           = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
    $appointment_date   = $_POST['appointment_date'];
    $start_time         = $_POST['start_time'];
    $end_time           = $_POST['end_time'];
    $appointment_status = $_POST['appointment_status'];
    $customer_notes     = trim($_POST['customer_notes']);

    // Check time constraint: end_time must be after start_time
    if ($start_time >= $end_time) {
        $error = "Validation Error: End time must be after start time.";
    } elseif ($customer_id > 0 && !empty($appointment_date) && !empty($start_time) && !empty($end_time)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO appointments 
                (customer_id, staff_id, appointment_date, start_time, end_time, appointment_status, customer_notes)
                VALUES (:customer_id, :staff_id, :appointment_date, :start_time, :end_time, :appointment_status, :customer_notes)
            ");
            $stmt->execute([
                'customer_id'        => $customer_id,
                'staff_id'           => $staff_id,
                'appointment_date'   => $appointment_date,
                'start_time'         => $start_time,
                'end_time'           => $end_time,
                'appointment_status' => $appointment_status,
                'customer_notes'     => $customer_notes ?: null
            ]);
            $message = "Appointment booked successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id     = (int)$_POST['appointment_id'];
    $customer_id        = (int)$_POST['customer_id'];
    $staff_id           = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
    $appointment_date   = $_POST['appointment_date'];
    $start_time         = $_POST['start_time'];
    $end_time           = $_POST['end_time'];
    $appointment_status = $_POST['appointment_status'];
    $customer_notes     = trim($_POST['customer_notes']);

    if ($start_time >= $end_time) {
        $error = "Validation Error: End time must be after start time.";
    } elseif ($appointment_id > 0 && $customer_id > 0 && !empty($appointment_date) && !empty($start_time) && !empty($end_time)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET customer_id = :customer_id, staff_id = :staff_id, appointment_date = :appointment_date, 
                    start_time = :start_time, end_time = :end_time, appointment_status = :appointment_status, 
                    customer_notes = :customer_notes 
                WHERE appointment_id = :appointment_id
            ");
            $stmt->execute([
                'customer_id'        => $customer_id,
                'staff_id'           => $staff_id,
                'appointment_date'   => $appointment_date,
                'start_time'         => $start_time,
                'end_time'           => $end_time,
                'appointment_status' => $appointment_status,
                'customer_notes'     => $customer_notes ?: null,
                'appointment_id'     => $appointment_id
            ]);
            $message = "Appointment details updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Required fields cannot be empty.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Appointment deleted successfully!";
    } catch (PDOException $e) {
        $error = "Cannot delete appointment (it may be linked to payment records).";
    }
}

$sql = "
    SELECT 
        a.appointment_id,
        a.customer_id,
        a.staff_id,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        CONCAT(s.first_name, ' ', s.last_name) AS staff_name,
        a.appointment_date,
        a.start_time,
        a.end_time,
        a.appointment_status,
        a.customer_notes
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN staff s ON s.staff_id = a.staff_id
    ORDER BY a.appointment_date DESC, a.start_time ASC
";
$appointments = $pdo->query($sql)->fetchAll();

$all_customers = $pdo->query("SELECT customer_id, first_name, last_name FROM customers WHERE account_status = 'Active' ORDER BY first_name ASC")->fetchAll();
$all_staff     = $pdo->query("SELECT staff_id, first_name, last_name, job_title FROM staff WHERE staff_status = 'Active' ORDER BY first_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="date"], input[type="time"], select {
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
        .bg-Confirmed { background-color: #007bff; }
        .bg-Completed { background-color: #28a745; }
        .bg-Cancelled { background-color: #dc3545; }
        .bg-NoShow { background-color: #6c757d; }
    </style>
</head>
<body>

    <h2>Appointments Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW APPOINTMENT FORM -->
    <div class="form-card">
        <h3>Book New Appointment</h3>
        <form method="POST" action="admin_appointments.php">
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
                    <label>Staff Member</label>
                    <select name="staff_id">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($all_staff as $st): ?>
                            <option value="<?= $st['staff_id'] ?>">
                                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name'] . ' (' . $st['job_title'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="appointment_status">
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="No Show">No Show</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" name="end_time" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Customer Notes</label>
                    <input type="text" name="customer_notes" placeholder="Special requests or comments...">
                </div>
            </div>
            <button type="submit" name="create_appointment" class="btn btn-add">Book Appointment</button>
        </form>
    </div>

    <!-- APPOINTMENTS TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Time Slot</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $apt): ?>
                <tr>
                    <?php if ($edit_id == $apt['appointment_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_appointments.php">
                            <td><?= $apt['appointment_id'] ?></td>
                            <td>
                                <input type="hidden" name="appointment_id" value="<?= $apt['appointment_id'] ?>">
                                <select name="customer_id" required>
                                    <?php foreach ($all_customers as $cust): ?>
                                        <option value="<?= $cust['customer_id'] ?>" <?= $cust['customer_id'] == $apt['customer_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="staff_id">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach ($all_staff as $st): ?>
                                        <option value="<?= $st['staff_id'] ?>" <?= $st['staff_id'] == $apt['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="appointment_date" value="<?= $apt['appointment_date'] ?>" required>
                            </td>
                            <td>
                                <input type="time" name="start_time" value="<?= $apt['start_time'] ?>" required>
                                <input type="time" name="end_time" value="<?= $apt['end_time'] ?>" required>
                            </td>
                            <td>
                                <select name="appointment_status">
                                    <?php 
                                    $statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];
                                    foreach ($statuses as $st_val): ?>
                                        <option value="<?= $st_val ?>" <?= $apt['appointment_status'] === $st_val ? 'selected' : '' ?>><?= $st_val ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="customer_notes" value="<?= htmlspecialchars($apt['customer_notes'] ?? '') ?>">
                            </td>
                            <td>
                                <button type="submit" name="update_appointment" class="btn btn-save">Save</button>
                                <a href="admin_appointments.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= $apt['appointment_id'] ?></td>
                        <td><?= htmlspecialchars($apt['customer_name']) ?></td>
                        <td><?= htmlspecialchars($apt['staff_name'] ?? 'Unassigned') ?></td>
                        <td><?= htmlspecialchars($apt['appointment_date']) ?></td>
                        <td><?= htmlspecialchars(substr($apt['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($apt['end_time'], 0, 5)) ?></td>
                        <td>
                            <span class="badge bg-<?= str_replace(' ', '', $apt['appointment_status']) ?>">
                                <?= htmlspecialchars($apt['appointment_status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($apt['customer_notes'] ?? '-') ?></td>
                        <td>
                            <a href="admin_appointments.php?edit=<?= $apt['appointment_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_appointments.php?delete=<?= $apt['appointment_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this appointment?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
