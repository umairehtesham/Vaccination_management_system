<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('staff') && !hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$message = '';

// register citizen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_citizen'])) {
    $full_name = sanitizeInput($_POST['full_name']);
    $username  = sanitizeInput($_POST['username']); // Login ke liye
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $email     = sanitizeInput($_POST['email']);
    $dob       = $_POST['date_of_birth'];
    $gender    = $_POST['gender'];
    $address   = $_POST['address'];
    $contact   = $_POST['contact'];
    $emergency = $_POST['emergency_contact'];

   
    $conn->begin_transaction();

    try {
        // add citizen in user table
        $stmt1 = $conn->prepare("INSERT INTO users (username, password, role, email, phone, is_active) VALUES (?, ?, 'citizen', ?, ?, 1)");
        $stmt1->bind_param("ssss", $username, $password, $email, $contact);
        $stmt1->execute();
        
        $new_user_id = $conn->insert_id; // Naye user ki ID le li

        // make citizen in citizen table and link using user_id
        $stmt2 = $conn->prepare("INSERT INTO citizens (user_id, full_name, date_of_birth, gender, address, contact, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issssss", $new_user_id, $full_name, $dob, $gender, $address, $contact, $emergency);
        $stmt2->execute();

        $conn->commit(); // save
        $message = "<div class='alert alert-success shadow-sm'>Citizen and Login Account registered successfully!</div>";
    } catch (Exception $e) {
        $conn->rollback(); // rollback in case of error
        $message = "<div class='alert alert-danger shadow-sm'>Error: Duplicate Username or Database Issue.</div>";
    }
}

// list of citizen
$citizens = $conn->query("SELECT * FROM citizens ORDER BY created_at DESC");

$pageTitle = "Manage Citizens - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-nurse"></i> Staff Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_citizens.php" class="active"><i class="fas fa-users"></i> Citizens</a>
            <a href="record_vaccination.php"><i class="fas fa-syringe"></i> Record Vaccination</a>
            <a href="manage_appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-users"></i> Citizen Management</h2>
            <?php echo $message; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Register New Citizen & Create Account</h5>
                </div>
                <div class="card-body bg-light">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Full Name</label>
                                <input type="text" name="full_name" class="form-control" placeholder="Enter Full Name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Gender</label>
                                <select name="gender" class="form-select form-control" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-weight-bold text-dark">Username (for login)</label>
                                <input type="text" name="username" class="form-control border-primary" placeholder="e.g. ali123" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold text-dark">Password</label>
                                <input type="password" name="password" class="form-control border-primary" placeholder="Set a password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="patient@example.com" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Contact Number</label>
                                <input type="text" name="contact" class="form-control" placeholder="03XXXXXXXXX" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Address</label>
                                <input type="text" name="address" class="form-control" placeholder="Home Address" required>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" name="add_citizen" class="btn btn-primary btn-lg px-5 shadow-sm">
                                    <i class="fas fa-check-circle"></i> Complete Registration
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th>DOB</th>
                                    <th>Address</th>
                                    <th>Emergency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($citizens->num_rows > 0): ?>
                                    <?php while($row = $citizens->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo $row['gender']; ?></td>
                                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($row['date_of_birth'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                                        <td><?php echo $row['emergency_contact'] ?: '<span class="text-muted">N/A</span>'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No citizens registered yet.</td></tr>
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