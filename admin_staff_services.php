<?php
require 'db.php';

$message = '';
$error = '';
$edit_staff_id = $_GET['edit_staff'] ?? null;
$edit_service_id = $_GET['edit_service'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_service'])) {
    $staff_id   = (int)$_POST['staff_id'];
    $service_id = (int)$_POST['service_id'];

    if ($staff_id > 0 && $service_id > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO staff_services (staff_id, service_id)
                VALUES (:staff_id, :service_id)
            ");
            $stmt->execute([
                'staff_id'   => $staff_id,
                'service_id' => $service_id
            ]);
            $message = "Service assigned to staff member successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Primary key / duplicate entry constraint error
                $error = "This staff member is already assigned to that service.";
            } else {
                $error = "Error assigning service: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please select both a staff member and a service.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $old_staff_id   = (int)$_POST['old_staff_id'];
    $old_service_id = (int)$_POST['old_service_id'];
    $new_staff_id   = (int)$_POST['staff_id'];
    $new_service_id = (int)$_POST['service_id'];

    if ($old_staff_id > 0 && $old_service_id > 0 && $new_staff_id > 0 && $new_service_id > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE staff_services 
                SET staff_id = :new_staff_id, service_id = :new_service_id 
                WHERE staff_id = :old_staff_id AND service_id = :old_service_id
            ");
            $stmt->execute([
                'new_staff_id'   => $new_staff_id,
                'new_service_id' => $new_service_id,
                'old_staff_id'   => $old_staff_id,
                'old_service_id' => $old_service_id
            ]);
            $message = "Staff service assignment updated successfully!";
            $edit_staff_id = null;
            $edit_service_id = null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "This assignment already exists for the selected staff member and service.";
            } else {
                $error = "Error updating assignment: " . $e->getMessage();
            }
        }
    } else {
        $error = "Invalid parameters for assignment update.";
    }
}

if (isset($_GET['delete_staff']) && isset($_GET['delete_service'])) {
    $del_staff_id   = (int)$_GET['delete_staff'];
    $del_service_id = (int)$_GET['delete_service'];

    try {
        $stmt = $pdo->prepare("DELETE FROM staff_services WHERE staff_id = :staff_id AND service_id = :service_id");
        $stmt->execute([
            'staff_id'   => $del_staff_id,
            'service_id' => $del_service_id
        ]);
        $message = "Staff service assignment removed successfully!";
    } catch (PDOException $e) {
        $error = "Error removing assignment: " . $e->getMessage();
    }
}

$sql = "
    SELECT 
        ss.staff_id,
        ss.service_id,
        CONCAT(s.first_name, ' ', s.last_name) AS staff_name,
        s.job_title,
        sv.service_name,
        sv.price
    FROM staff_services ss
    JOIN staff s ON s.staff_id = ss.staff_id
    JOIN services sv ON sv.service_id = ss.service_id
    ORDER BY s.first_name ASC, sv.service_name ASC
";
$assignments = $pdo->query($sql)->fetchAll();

$all_staff = $pdo->query("SELECT staff_id, first_name, last_name, job_title FROM staff WHERE staff_status != 'Inactive' ORDER BY first_name ASC")->fetchAll();
$all_services = $pdo->query("SELECT service_id, service_name, price FROM services WHERE service_status = 'Available' ORDER BY service_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Services Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
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
</head>
<body>

    <h2>Staff Services Assignment</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ASSIGN NEW SERVICE FORM -->
    <div class="form-card">
        <h3>Assign Service to Staff Member</h3>
        <form method="POST" action="admin_staff_services.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Staff Member *</label>
                    <select name="staff_id" required>
                        <option value="">-- Select Staff Member --</option>
                        <?php foreach ($all_staff as $st): ?>
                            <option value="<?= $st['staff_id'] ?>">
                                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name'] . ' (' . $st['job_title'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service *</label>
                    <select name="service_id" required>
                        <option value="">-- Select Service --</option>
                        <?php foreach ($all_services as $sv): ?>
                            <option value="<?= $sv['service_id'] ?>">
                                <?= htmlspecialchars($sv['service_name'] . ' - MUR ' . number_format($sv['price'], 2)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="assign_service" class="btn btn-add">Assign Service</button>
        </form>
    </div>

    <!-- ASSIGNMENTS TABLE -->
    <table>
        <thead>
            <tr>
                <th>Staff Member</th>
                <th>Job Title</th>
                <th>Assigned Service</th>
                <th>Service Price (MUR)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
                <tr>
                    <?php if ($edit_staff_id == $a['staff_id'] && $edit_service_id == $a['service_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_staff_services.php">
                            <input type="hidden" name="old_staff_id" value="<?= $a['staff_id'] ?>">
                            <input type="hidden" name="old_service_id" value="<?= $a['service_id'] ?>">
                            <td>
                                <select name="staff_id" required>
                                    <?php foreach ($all_staff as $st): ?>
                                        <option value="<?= $st['staff_id'] ?>" <?= $st['staff_id'] == $a['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($a['job_title']) ?></td>
                            <td>
                                <select name="service_id" required>
                                    <?php foreach ($all_services as $sv): ?>
                                        <option value="<?= $sv['service_id'] ?>" <?= $sv['service_id'] == $a['service_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= number_format($a['price'], 2) ?></td>
                            <td>
                                <button type="submit" name="update_assignment" class="btn btn-save">Save</button>
                                <a href="admin_staff_services.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= htmlspecialchars($a['staff_name']) ?></td>
                        <td><?= htmlspecialchars($a['job_title']) ?></td>
                        <td><?= htmlspecialchars($a['service_name']) ?></td>
                        <td><?= number_format($a['price'], 2) ?></td>
                        <td>
                            <a href="admin_staff_services.php?edit_staff=<?= $a['staff_id'] ?>&edit_service=<?= $a['service_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_staff_services.php?delete_staff=<?= $a['staff_id'] ?>&delete_service=<?= $a['service_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Remove this service assignment from the staff member?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
