<?php

//imports the db
require "db.php";

$message = null;
$error = null;
$edit_id = $_GET["edit"] ?? null;

//code to add new customer
//validates if POST request received for create_customer
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_customer"])) {
	$first_name			= trim($_POST["first_name"]);
	$last_name			= trim($_POST["last_name"]);
	$email				= trim($_POST["email"]);
	$phone				= trim($_POST["phone"]);
	$plain_password		= ($_POST["password"]);
	$address			= trim($_POST["address"]);
	$account_status		= ($_POST["account_status"]);

	//making sure that all fields are filled in
	if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($phone) && !empty($plain_password)) {
		try {
			//hashing password
			$password_hash = password_hash($plain_password, PASSWORD_DEFAULT);

			//prepared statement to enter the values into the table
			$stmt = $pdo->prepare("
				INSERT INTO customers (first_name, last_name, email, phone, password_hash, address, account_status)
				VALUES (:first_name, :last_name, :email, :phone, :password_hash, :address, :account_status)"
			);

			//execution by matching PHP to their placeholders
			$stmt->execute([
				"first_name"		=> $first_name,
				"last_name"			=> $last_name,
				"email"				=> $email,
				"phone"				=> $phone,
				"password_hash"		=> $password_hash,
				"address"			=> $address ?: null,
				"account_status"	=> $account_status,
			]);
			$message = "Customer added successfully.";
		} catch (PDOException $e) {
			$error = "Error adding customer: " . $e->getMessage();
		}
	} else {
		$error = "Please fill in all required fields.";
	}

}

//updating a customer's credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
	$customer_id		= $_POST["customer_id"];
	$first_name			= trim($_POST["first_name"]);
	$last_name			= trim($_POST["last_name"]);
	$email				= trim($_POST["email"]);
	$phone				= trim($_POST["phone"]);
	$address			= trim($_POST["address"]);
	$account_status		= $_POST["account_status"];
	$new_password			= $_POST["password"];

	if (!empty($customer_id) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($phone)) {
		try {
			//checking if new password entered to update
			if (!empty($new_password)) {
				$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

				$stmt = $pdo->prepare("
					UPDATE customers
					SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone,
						password_hash = :password_hash, address = :address, account_status = :account_status
					WHERE customer_id = :customer_id
				");

				$stmt->execute([
				"first_name"		=> $first_name,
				"last_name"			=> $last_name,
				"email"				=> $email,
				"phone"				=> $phone,
				"password_hash"		=> $password_hash,
				"address"			=> $address ?: null,
				"account_status"	=> $account_status,
				"customer_id"		=> $customer_id	
				]);
			} else {
				//keeping the existing password if field is blank
				$stmt = $pdo->prepare("
					UPDATE customers
					SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone,
						address = :address, account_status = :account_status
					WHERE customer_id = :customer_id
				");

				$stmt->execute([
				"first_name"		=> $first_name,
				"last_name"			=> $last_name,
				"email"				=> $email,
				"phone"				=> $phone,
				"address"			=> $address ?: null,
				"account_status"	=> $account_status,
				"customer_id"		=> $customer_id,		
				]);
			}
			$message = "Customer details updated successfully.";
			//return table row to view mode after successful update
			$edit_id = null;
		} catch (PDOException $e) {
			$error = "Error updating customer: " . $e->getMessage();
		}
	} else {
		$error = "Required fields cannot be empty.";
	}
}

//deleting of customer credentials
if (isset($_GET["delete"])) {
	$delete_id = $_GET["delete"];
	try {
		$stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = :id");
		$stmt->execute(['id' => $delete_id]);
		$message = "Customer deleted successfully!";
	} catch (PDOException $e) {
		$error = "Cannot delete customer (they may have existing appointments or orders).";
	}
}

//displaying the full table
$stmt = $pdo->query("SELECT * FROM customers ORDER BY customer_id ASC");
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>Customer Management</title>

	<style>
		body { font-family: Arial, sans-serif; margin: 30px; background-color:#f8f9fa;}
		h2, h3 { color: #2c3e50; }
		.alert-success { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
		.alert-error { padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px; }
		.form-card { background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
		.form-row { display: flex; gap: 15px; margin-bottom: 10px; }
		.form-group { flex: 1; display: flex; flex-direction: column; }
		.form-group label { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
		input[type="text"], input[type="email"], input[type="password"], select {
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
		.bg-Inactive { background-color: #6c757d; }
		.bg-Blocked { background-color: #dc3545; }
	</style>
</head>

<body>

	<h2>Customer Management</h2>

	<!-- displaying message -->	
	<?php if ($message): ?>
		<div class="alert-success"><?= htmlspecialchars($message) /*escapes special characters against XSS*/ ?></div> 
	<?php endif; ?>

	<?php if ($error): /*displaying error message*/?>
		<div class="alert-error"><?= htmlspecialchars($error) ?></div>
	<?php endif; ?>

	
	<!-- ADD NEW CUSTOMER FORM -->
	<div class="form-card">

		<h3>Add New Custoemr</h3>

		<form method="POST" action="admin_customers.php">

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
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Account Status</label>
                    <select name="account_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
			</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="e.g. Mahebourg, Mauritius">
                </div>
            </div>
            <button type="submit" name="create_customer" class="btn btn-add">Add Customer</button>
		</form>
	</div>

	<!-- CUSTOMERS TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

		<tbody>
            <?php foreach ($customers as $c): ?>
				<tr>
					<?php if ($edit_id == $c['customer_id']): ?>
						
						<!-- EDIT MODE ROW -->
						<form method="POST" action="admin_customers.php">
                            <td><?= $c['customer_id'] ?></td>
                            <td>
								<!-- customer_id made hidden so that is cannot be edited (is primary key) -->
                                <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
                                <input type="text" name="first_name" value="<?= htmlspecialchars($c['first_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($c['last_name']) ?>" required>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($c['email']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="phone" value="<?= htmlspecialchars($c['phone']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="address" value="<?= htmlspecialchars($c['address'] ?? '') ?>">
                            </td>
                            <td>
                                <select name="account_status">
                                    <option value="Active" <?= $c['account_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $c['account_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="Blocked" <?= $c['account_status'] === 'Blocked' ? 'selected' : '' ?>>Blocked</option>
                                </select>
                                <br><small style="color:gray;">New Password (optional):</small>
                                <input type="password" name="password" placeholder="Leave blank to keep current">
                            </td>
                            <td>
                                <button type="submit" name="update_customer" class="btn btn-save">Save</button>
                                <a href="admin_customers.php" class="btn btn-cancel">Cancel</a>
                            </td>
                        </form>

					<?php else: ?>
						<!-- VIEW MODE ROW -->
                        <td><?= $c['customer_id'] ?></td>
                        <td><?= htmlspecialchars($c['first_name']) ?></td>
                        <td><?= htmlspecialchars($c['last_name']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['address'] ?? '-') ?></td>
                        <td>
							<!-- renders the lettering different depending on account status -->
                            <span class="badge bg-<?= $c['account_status'] ?>">
                                <?= $c['account_status'] ?>
                            </span>
                        </td>
                        <td>
							<!-- link to click if selected customer is to be edited -->
                            <a href="admin_customers.php?edit=<?= $c['customer_id'] ?>" class="btn btn-edit">Edit</a>
							<!-- link to click if selected customer is to be deleted -->
                            <a href="admin_customers.php?delete=<?= $c['customer_id'] ?>" 
                               class="btn btn-delete" 
								<?php /*safety prompt to confirm if record is to be deleted*/?>
                               onclick="return confirm('Delete this customer?');">Delete</a>
                        </td>
					<?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
