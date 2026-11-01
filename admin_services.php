<?php
require 'db.php';

$message = '';
$error = '';
$edit_id = $_GET['edit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_service'])) {
    $service_name     = trim($_POST['service_name']);
    $description      = trim($_POST['description']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $price            = (float)$_POST['price'];
    $service_status   = $_POST['service_status'];

    if (!empty($service_name) && $duration_minutes > 0 && $price >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO services (service_name, description, duration_minutes, price, service_status)
                VALUES (:service_name, :description, :duration_minutes, :price, :service_status)
            ");
            $stmt->execute([
                'service_name'     => $service_name,
                'description'      => $description ?: null,
                'duration_minutes' => $duration_minutes,
                'price'            => $price,
                'service_status'   => $service_status
            ]);
            $message = "Service added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding service: " . $e->getMessage();
        }
    } else {
        $error = "Please provide a valid service name, positive duration, and valid price.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $service_id       = $_POST['service_id'];
    $service_name     = trim($_POST['service_name']);
    $description      = trim($_POST['description']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $price            = (float)$_POST['price'];
    $service_status   = $_POST['service_status'];

    if (!empty($service_id) && !empty($service_name) && $duration_minutes > 0 && $price >= 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE services 
                SET service_name = :service_name, description = :description, 
                    duration_minutes = :duration_minutes, price = :price, service_status = :service_status 
                WHERE service_id = :service_id
            ");
            $stmt->execute([
                'service_name'     => $service_name,
                'description'      => $description ?: null,
                'duration_minutes' => $duration_minutes,
                'price'            => $price,
                'service_status'   => $service_status,
                'service_id'       => $service_id
            ]);
            $message = "Service updated successfully!";
            $edit_id = null;
        } catch (PDOException $e) {
            $error = "Error updating service: " . $e->getMessage();
        }
    } else {
        $error = "Required fields cannot be empty or invalid.";
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE service_id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Service deleted successfully!";
    } catch (PDOException $e) {
        $error = "Cannot delete service (it is linked to existing appointments or staff assignments).";
    }
}

$stmt = $pdo->query("SELECT * FROM services ORDER BY service_id ASC");
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Salon System</title>
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
        .bg-Unavailable { background-color: #dc3545; }
    </style>
</head>
<body>

    <h2>Service Management</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD NEW SERVICE FORM -->
    <div class="form-card">
        <h3>Add New Service</h3>
        <form method="POST" action="admin_services.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="service_name" placeholder="e.g. Hair Styling" required>
                </div>
                <div class="form-group">
                    <label>Duration (Minutes) *</label>
                    <input type="number" name="duration_minutes" min="1" placeholder="e.g. 60" required>
                </div>
                <div class="form-group">
                    <label>Price (MUR) *</label>
                    <input type="number" step="0.01" name="price" min="0" placeholder="e.g. 900.00" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="service_status">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Brief service description...">
                </div>
            </div>
            <button type="submit" name="create_service" class="btn btn-add">Add Service</button>
        </form>
    </div>

    <!-- SERVICES TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Description</th>
                <th>Duration (Mins)</th>
                <th>Price (MUR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $s): ?>
                <tr>
                    <?php if ($edit_id == $s['service_id']): ?>
                        <!-- EDIT MODE ROW -->
                        <form method="POST" action="admin_services.php">
                            <td><?= $s['service_id'] ?></td>
                            <td>
                                <input type="hidden" name="service_id" value="<?= $s['service_id'] ?>">
                                <input type="text" name="service_name" value="<?= htmlspecialchars($s['service_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description" value="<?= htmlspecialchars($s['description'] ?? '') ?>">
                            </td>
                            <td>
                                <input type="number" name="duration_minutes" min="1" value="<?= $s['duration_minutes'] ?>" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="price" min="0" value="<?= $s['price'] ?>" required>
                            </td>
                            <td>
                                <select name="service_status">
                                    <option value="Available" <?= $s['service_status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                    <option value="Unavailable" <?= $s['service_status'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_service" class="btn btn-save">Save</button>
                                <a href="admin_services.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>
                    <?php else: ?>
                        <!-- VIEW MODE ROW -->
                        <td><?= $s['service_id'] ?></td>
                        <td><?= htmlspecialchars($s['service_name']) ?></td>
                        <td><?= htmlspecialchars($s['description'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['duration_minutes']) ?> min</td>
                        <td><?= number_format($s['price'], 2) ?></td>
                        <td>
                            <span class="badge bg-<?= $s['service_status'] ?>">
                                <?= $s['service_status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_services.php?edit=<?= $s['service_id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="admin_services.php?delete=<?= $s['service_id'] ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
