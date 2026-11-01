<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $first_name   = trim($_POST['first_name']);
    $last_name    = trim($_POST['last_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $job_title    = trim($_POST['job_title']);
    $hire_date    = $_POST['hire_date'];
    $staff_status = $_POST['staff_status'];

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($phone) && !empty($job_title) && !empty($hire_date)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO staff (first_name, last_name, email, phone, job_title, hire_date, staff_status)
                VALUES (:first_name, :last_name, :email, :phone, :job_title, :hire_date, :staff_status)
            ");
            $stmt->execute([
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'email'        => $email,
                'phone'        => $phone,
                'job_title'    => $job_title,
                'hire_date'    => $hire_date,
                'staff_status' => $staff_status
            ]);
            $message = "Staff member added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding staff: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $staff_id     = $_POST['staff_id'];
    $first_name   = trim($_POST['first_name']);
    $last_name    = trim($_POST['last_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $job_title    = trim($_POST['job_title']);
    $hire_date    = $_POST['hire_date'];
    $staff_status = $_POST['staff_status'];

    if (!empty($staff_id) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($phone) && !empty($job_title) && !empty($hire_date)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE staff 
                SET first_name = :first_name, last_name = :last_name, email = :email, 
                    phone = :phone, job_title = :job_title, hire_date = :hire_date, staff_status = :staff_status 
                WHERE staff_id = :staff_id
            ");
            $stmt->execute([
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'email'        => $email,
                'phone'        => $phone,
                'job_title'    => $job_title,
                'hire_date'    => $hire_date,
                'staff_status' => $staff_status,
                'staff_id'     => $staff_id
            ]);
            $message = "Staff member details updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            $error = "Error updating staff member: " . $e->getMessage();
        }
    } else {
        $error = "Required fields cannot be empty.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Staff member deleted successfully!";
    } catch (PDOException $e) {
        $error = "Cannot delete staff member (they might be linked to appointments or assigned services).";
    }
}

// 4. READ: Fetch all staff records from database
$stmt = $pdo->query("SELECT * FROM staff ORDER BY staff_id ASC");
$staff_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Salon System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        h2, h3 { color: #2c3e50; }
        .alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
        .form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="email"], input[type="date"], select {
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
        .bg-Active { background-color: #28a745; }
        .bg-OnLeave { background-color: #ffc107; color: black; }
        .bg-Inactive { background-color: #6c757d; }
    </style>
</head>
<body>

    <h2>Staff Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW STAFF FORM -->
    <div class="form-card">
        <h3>Add New Staff Member</h3>
        <form method="POST" action="admin_staff.php">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="job_title" placeholder="e.g. Hair Stylist" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Hire Date *</label>
                    <input type="date" name="hire_date" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="staff_status">
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="create_staff" class="btn btn-add">Add Staff Member</button>
        </form>
    </div>

    <!-- STAFF TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Job Title</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Hire Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff_members as $s): ?>
                <tr>
                    <?php if ($edit_id == $s['staff_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_staff.php">
                            <td><?= $s['staff_id'] ?></td>
                            <td>
                                <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
                                <input type="text" name="first_name" value="<?= htmlspecialchars($s['first_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($s['last_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="job_title" value="<?= htmlspecialchars($s['job_title']) ?>" required>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($s['email']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="phone" value="<?= htmlspecialchars($s['phone']) ?>" required>
                            </td>
                            <td>
                                <input type="date" name="hire_date" value="<?= $s['hire_date'] ?>" required>
                            </td>
                            <td>
                                <select name="staff_status">
                                    <option value="Active" <?= $s['staff_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="On Leave" <?= $s['staff_status'] === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="Inactive" <?= $s['staff_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_staff" class="btn btn-save">Save</button>
                                <a href="admin_staff.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= $s['staff_id'] ?></td>
                        <td><?= htmlspecialchars($s['first_name']) ?></td>
                        <td><?= htmlspecialchars($s['last_name']) ?></td>
                        <td><?= htmlspecialchars($s['job_title']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['hire_date']) ?></td>
                        <td>
                            <!-- Formats status name to match CSS class (removes spaces, e.g., OnLeave) -->
                            <span class="badge bg-<?= str_replace(' ', '', $s['staff_status']) ?>">
                                <?= htmlspecialchars($s['staff_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_staff.php?edit=<?= $s['staff_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_staff.php?delete=<?= $s['staff_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this staff member?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
