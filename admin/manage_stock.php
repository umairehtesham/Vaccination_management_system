<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $v_id = $_POST['vaccine_id'];
    $batch = sanitizeInput($_POST['batch_number']);
    $qty = (int)$_POST['quantity'];
    $expiry = $_POST['expiry_date'];

    $stmt = $conn->prepare("INSERT INTO vaccine_stock (vaccine_id, batch_number, quantity, expiry_date, received_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isis", $v_id, $batch, $qty, $expiry);
    $stmt->execute();
}

$vaccines = $conn->query("SELECT * FROM vaccines");
$stock_list = $conn->query("SELECT s.*, v.vaccine_name FROM vaccine_stock s JOIN vaccines v ON s.vaccine_id = v.vaccine_id ORDER BY s.received_date DESC");

$pageTitle = "Manage Stock - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-shield"></i> Admin Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_vaccines.php"><i class="fas fa-syringe"></i> Manage Vaccines</a>
            <a href="manage_stock.php" class="active"><i class="fas fa-boxes"></i> Manage Stock</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-boxes"></i> Inventory & Stock</h2>

            <div class="card dashboard-card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add Stock Entry</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <select name="vaccine_id" class="form-control" required>
                                    <option value="">Select Vaccine</option>
                                    <?php $vaccines->data_seek(0); while($v = $vaccines->fetch_assoc()) echo "<option value='{$v['vaccine_id']}'>{$v['vaccine_name']}</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2"><input type="text" name="batch_number" class="form-control" placeholder="Batch No" required></div>
                            <div class="col-md-2 mb-2"><input type="number" name="quantity" class="form-control" placeholder="Quantity" required></div>
                            <div class="col-md-2 mb-2"><input type="date" name="expiry_date" class="form-control" required title="Expiry Date"></div>
                            <div class="col-md-2 mb-2"><button type="submit" name="add_stock" class="btn btn-success w-100">Add Stock</button></div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vaccine</th><th>Batch #</th><th>Quantity</th><th>Expiry</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($s = $stock_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $s['vaccine_name']; ?></strong></td>
                                <td><?php echo $s['batch_number']; ?></td>
                                <td><?php echo $s['quantity']; ?></td>
                                <td><?php echo $s['expiry_date']; ?></td>
                                <td>
                                    <?php 
                                    $today = date('Y-m-d');
                                    echo ($s['expiry_date'] < $today) ? '<span class="badge bg-danger">Expired</span>' : '<span class="badge bg-success">Active</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>