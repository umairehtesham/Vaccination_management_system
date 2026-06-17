<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('staff')) {
    header('Location: ../login.php');
    exit();
}

$message = '';

// new appointment 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    
    $citizen_id = $_POST['citizen_id'];
    $vaccine_id = $_POST['vaccine_id'];
    $center_id  = $_POST['center_id'];
    $app_date   = $_POST['scheduled_date'];
    $status     = 'scheduled'; 
    try {
        
        $sql = "INSERT INTO appointments (citizen_id, vaccine_id, center_id, scheduled_date, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $citizen_id, $vaccine_id, $center_id, $app_date, $status);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'><strong>Success!</strong> Appointment has been scheduled successfully.</div>";
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        
        $errorMessage = $e->getMessage();
        $message = "<div class='alert alert-danger'><strong>Booking Failed:</strong> " . $errorMessage . "</div>";
    }
}

// all appointments
$appointments = $conn->query("
    SELECT a.appointment_id, c.full_name, v.vaccine_name, vc.center_name, a.scheduled_date, a.status
    FROM appointments a
    JOIN citizens c ON a.citizen_id = c.citizen_id
    JOIN vaccines v ON a.vaccine_id = v.vaccine_id
    LEFT JOIN vaccination_centers vc ON a.center_id = vc.center_id
    ORDER BY a.scheduled_date DESC
");

//data for drop down
$citizens = $conn->query("SELECT citizen_id, full_name FROM citizens");
$vaccines = $conn->query("SELECT vaccine_id, vaccine_name FROM vaccines");
$centers = $conn->query("SELECT center_id, center_name FROM vaccination_centers");

$pageTitle = "Manage Appointments - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-nurse"></i> Staff Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_citizens.php"><i class="fas fa-users"></i> Citizens</a>
            <a href="record_vaccination.php"><i class="fas fa-syringe"></i> Record Vaccination</a>
            <a href="manage_appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Appointments</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-calendar-check"></i> Appointment Management</h2>
            <?php echo $message; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Book New Appointment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Citizen</label>
                                <select name="citizen_id" class="form-select form-control" required>
                                    <option value="">Select Citizen</option>
                                    <?php while($row = $citizens->fetch_assoc()) echo "<option value='".$row['citizen_id']."'>".$row['full_name']."</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vaccine</label>
                                <select name="vaccine_id" class="form-select form-control" required>
                                    <option value="">Select Vaccine</option>
                                    <?php while($row = $vaccines->fetch_assoc()) echo "<option value='".$row['vaccine_id']."'>".$row['vaccine_name']."</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Center</label>
                                <select name="center_id" class="form-select form-control" required>
                                    <option value="">Select Center</option>
                                    <?php while($row = $centers->fetch_assoc()) echo "<option value='".$row['center_id']."'>".$row['center_name']."</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date & Time</label>
                                <input type="datetime-local" name="scheduled_date" class="form-control" required>
                            </div>
                            <div class="col-12 mt-3 text-end">
                                <button type="submit" name="book_appointment" class="btn btn-warning shadow-sm">
                                    <i class="fas fa-plus"></i> Confirm Appointment
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
                                    <th>Citizen</th>
                                    <th>Vaccine</th>
                                    <th>Center</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($appointments && $appointments->num_rows > 0): ?>
                                    <?php while($row = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['vaccine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['center_name']); ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($row['scheduled_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($row['status'] == 'completed') ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No appointments found.</td></tr>
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