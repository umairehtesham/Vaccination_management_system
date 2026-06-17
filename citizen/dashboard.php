<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('citizen')) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];


$citizen_query = $conn->prepare("SELECT * FROM citizens WHERE user_id = ?");
$citizen_query->bind_param("i", $user_id);
$citizen_query->execute();
$citizen = $citizen_query->get_result()->fetch_assoc();
$citizen_query->close();

$citizen_id = $citizen['citizen_id'] ?? null;

// 1. Fetch History with calculated next dose dates
$vaccination_history = [];
if ($citizen_id) {
    $vax_query = $conn->prepare("
        SELECT vr.*, v.vaccine_name 
        FROM vaccination_records vr 
        JOIN vaccines v ON vr.vaccine_id = v.vaccine_id 
        WHERE vr.citizen_id = ? 
        ORDER BY vr.date_administered DESC
    ");
    $vax_query->bind_param("i", $citizen_id);
    $vax_query->execute();
    $vaccination_history = $vax_query->get_result();
    $vax_query->close();
}

// 2. Fetch Appointments (Today and Future)
$upcoming_appointments = [];
if ($citizen_id) {
    $appt_query = $conn->prepare("
        SELECT a.appointment_id, v.vaccine_name, a.scheduled_date, a.status, vc.center_name 
        FROM appointments a 
        JOIN vaccines v ON a.vaccine_id = v.vaccine_id 
        LEFT JOIN vaccination_centers vc ON a.center_id = vc.center_id 
        WHERE a.citizen_id = ? 
        AND a.status = 'scheduled' 
        AND DATE(a.scheduled_date) >= CURDATE() 
        ORDER BY a.scheduled_date ASC
    ");
    $appt_query->bind_param("i", $citizen_id);
    $appt_query->execute();
    $upcoming_appointments = $appt_query->get_result();
    $appt_query->close();
}

$pageTitle = "My Dashboard - VMS";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="mb-0"><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($citizen['full_name']); ?></h2>
        </div>
        <div class="col text-end">
            <a href="generate_certificate.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-print"></i> Get Vaccination Certificate
            </a>
        </div>
    </div>

    <?php if ($citizen): ?>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-light border-start border-primary border-4">
                <div class="row">
                    <div class="col-md-3"><strong>DOB:</strong><br><?php echo date('d M, Y', strtotime($citizen['date_of_birth'])); ?></div>
                    <div class="col-md-3"><strong>Gender:</strong><br><?php echo htmlspecialchars($citizen['gender']); ?></div>
                    <div class="col-md-3"><strong>Contact:</strong><br><?php echo htmlspecialchars($citizen['contact']); ?></div>
                    <div class="col-md-3"><strong>ID:</strong><br>VMS-<?php echo str_pad($citizen['citizen_id'], 5, '0', STR_PAD_LEFT); ?></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-success text-dark">
                <h5 class="mb-0"><i class="fas fa-syringe"></i> My Vaccination History</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($vaccination_history && $vaccination_history->num_rows > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vaccine</th>
                                <th>Dose</th>
                                <th>Date Taken</th>
                                <th>Status / Next Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rec = $vaccination_history->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rec['vaccine_name']); ?></strong></td>
                                <td><span class="badge bg-primary">Dose <?php echo $rec['dose_number']; ?></span></td>
                                <td><?php echo date('d-M-Y', strtotime($rec['date_administered'])); ?></td>
                                <td>
                                    <?php if ($rec['next_dose_date']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock"></i> Next Dose: <?php echo date('d-M-Y', strtotime($rec['next_dose_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Course Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No vaccination history found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> My Upcoming Appointments</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Vaccine</th><th>Date & Time</th><th>Center</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($appt = $upcoming_appointments->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($appt['vaccine_name']); ?></strong></td>
                                <td><?php echo date('d M, Y | h:i A', strtotime($appt['scheduled_date'])); ?></td>
                                <td><?php echo htmlspecialchars($appt['center_name'] ?? 'General Center'); ?></td>
                                <td><span class="badge bg-info text-dark">Scheduled</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No upcoming appointments scheduled.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">No citizen profile found. Please contact support.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>