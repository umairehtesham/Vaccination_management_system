<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Access Control
requireLogin();
if (!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vaccine'])) {
    $name = sanitizeInput($_POST['vaccine_name']);
    $manufacturer = sanitizeInput($_POST['manufacturer']);
    $doses = (int)$_POST['doses_required'];
    $category_id = (int)$_POST['category_id'];

    $stmt = $conn->prepare("INSERT INTO vaccines (vaccine_name, manufacturer, doses_required, category_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $manufacturer, $doses, $category_id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success alert-dismissible fade show'>Vaccine added successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_vaccine'])) {
    $id = (int)$_POST['vaccine_id'];
    $name = sanitizeInput($_POST['vaccine_name']);
    $manufacturer = sanitizeInput($_POST['manufacturer']);
    $doses = (int)$_POST['doses_required'];
    $category_id = (int)$_POST['category_id'];

    $stmt = $conn->prepare("UPDATE vaccines SET vaccine_name=?, manufacturer=?, doses_required=?, category_id=? WHERE vaccine_id=?");
    $stmt->bind_param("ssiii", $name, $manufacturer, $doses, $category_id, $id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-info alert-dismissible fade show'>Vaccine updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger'>Update Error: " . $conn->error . "</div>";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    
    $check_usage = $conn->query("SELECT record_id FROM vaccination_records WHERE vaccine_id = $id LIMIT 1");
    if ($check_usage->num_rows > 0) {
        $message = "<div class='alert alert-warning'>Cannot delete: This vaccine has existing vaccination records.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM vaccines WHERE vaccine_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-danger alert-dismissible fade show'>Vaccine deleted! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}


$categories_query = $conn->query("SELECT * FROM vaccine_categories");
$categories = []; // Store for edit modals
while($row = $categories_query->fetch_assoc()) { $categories[] = $row; }

$vaccines_query = $conn->query("
    SELECT v.*, vc.category_name 
    FROM vaccines v 
    LEFT JOIN vaccine_categories vc ON v.category_id = vc.category_id 
    ORDER BY v.vaccine_id DESC
");

$pageTitle = "Manage Vaccines - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-shield"></i> Admin Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_vaccines.php" class="active"><i class="fas fa-syringe"></i> Manage Vaccines</a>
            <a href="manage_stock.php"><i class="fas fa-boxes"></i> Manage Stock</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-syringe"></i> Vaccine Management</h2>
            
            <?php echo $message; ?>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Vaccine Type</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Vaccine Name</label>
                                <input type="text" name="vaccine_name" class="form-control" placeholder="e.g. Pfizer" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control" placeholder="e.g. BioNTech" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select...</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Doses Required</label>
                                <input type="number" name="doses_required" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end mb-3">
                                <button type="submit" name="add_vaccine" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Save Vaccine
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Available Vaccines</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Vaccine Name</th>
                                    <th>Manufacturer</th>
                                    <th>Category</th>
                                    <th>Doses</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($vaccines_query->num_rows > 0): ?>
                                    <?php while($v = $vaccines_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $v['vaccine_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($v['vaccine_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($v['manufacturer']); ?></td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($v['category_name'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo $v['doses_required']; ?> Dose(s)</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $v['vaccine_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <a href="?delete=<?php echo $v['vaccine_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this vaccine?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal<?php echo $v['vaccine_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Vaccine</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="vaccine_id" value="<?php echo $v['vaccine_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Vaccine Name</label>
                                                            <input type="text" name="vaccine_name" class="form-control" value="<?php echo htmlspecialchars($v['vaccine_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Manufacturer</label>
                                                            <input type="text" name="manufacturer" class="form-control" value="<?php echo htmlspecialchars($v['manufacturer']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Category</label>
                                                            <select name="category_id" class="form-control" required>
                                                                <?php foreach($categories as $cat): ?>
                                                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($v['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $cat['category_name']; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Doses Required</label>
                                                            <input type="number" name="doses_required" class="form-control" value="<?php echo $v['doses_required']; ?>" min="1" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_vaccine" class="btn btn-primary">Update Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No vaccines found in the database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>