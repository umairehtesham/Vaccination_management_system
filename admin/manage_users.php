<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$message = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, email, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssss", $username, $password, $role, $email, $phone);
        $stmt->execute();
        
        $conn->commit();
        $message = "<div class='alert alert-success'>User added successfully!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}


if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $check_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $user_to_delete = $check_stmt->get_result()->fetch_assoc();

    if ($id != $_SESSION['user_id'] && $user_to_delete['role'] != 'admin') {
        $conn->query("DELETE FROM citizens WHERE user_id = $id");
        $conn->query("DELETE FROM users WHERE user_id = $id");
        header("Location: manage_users.php?msg=deleted");
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Action Denied: Admin accounts are protected and cannot be deleted.</div>";
    }
}


$users_query = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$pageTitle = "Manage Users - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-shield"></i> Admin Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_vaccines.php"><i class="fas fa-syringe"></i> Manage Vaccines</a>
            <a href="manage_stock.php"><i class="fas fa-boxes"></i> Manage Stock</a>
            <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-users"></i> User Management</h2>
            
            <?php echo $message; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New Staff Member</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="e.g. ali_khan" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="ali@example.com" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="03XXXXXXXXX">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="Staff Member" readonly disabled>
                                <input type="hidden" name="role" value="staff">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end mb-3">
                                <button type="submit" name="add_user" class="btn btn-primary w-100 shadow-sm">
                                    <i class="fas fa-save"></i> Create Account
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Registered Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users_query->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo ($user['role'] == 'admin') ? 'bg-danger' : (($user['role'] == 'staff') ? 'bg-primary' : 'bg-success'); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $user['is_active'] ? '<span class="text-success small"><i class="fas fa-circle"></i> Active</span>' : '<span class="text-muted small">Inactive</span>'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Delete button tabhi dikhayen jab user Admin na ho aur khud login na ho
                                        if($user['user_id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                            <a href="?delete=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Deleting this user will also delete their citizen profile. Continue?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php elseif($user['role'] == 'admin'): ?>
                                            <span class="text-muted small" title="Admin accounts cannot be deleted"><i class="fas fa-lock"></i> Protected</span>
                                        <?php endif; ?>
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
</div>

<?php include '../includes/footer.php'; ?>